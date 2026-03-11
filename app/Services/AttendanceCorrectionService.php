<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceCorrection;
use App\Services\Attendance\Validators\TimeValidator;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceCorrectionService
{
    protected TimeValidator $timeValidator;

    public function __construct(TimeValidator $timeValidator)
    {
        $this->timeValidator = $timeValidator;
    }

    /**
     * Get all attendance correction records with role-based filtering.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index($user)
    {
        // 1. Initialize query with necessary relationships
        $query = AttendanceCorrection::with(['employee.user', 'attendance', 'approver.user']);

        // 2. High-level Roles (Admin, Owner, Director, HR, Finance) -> Can see all data
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // No filter applied
        }
        // 3. Manager -> Can see own requests and direct subordinates
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $employeeId = $user->employee->id;
            $query->where(function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId)
                    ->orWhereHas('employee', fn ($sq) => $sq->where('manager_id', $employeeId));
            });
        } else {
            // 4. Employee -> Can only see their own requests
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    /**
     * Get a list of pending attendance correction requests that require approval.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function indexApproval($user)
    {
        // 1. Initialize base query for pending requests
        $query = AttendanceCorrection::with(['employee.user', 'attendance', 'approver.user'])
            ->pending()
            ->whereNull('approved_at');

        // 2. High-level Roles -> Full access to all pending requests
        if ($user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
            // No additional filter
        }

        // 3. Manager Logic -> Can only see approvals for direct subordinates
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $employeeId));
        }

        // 4. Fallback for other roles
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    /**
     * Show details of a specific attendance correction request.
     *
     * @return AttendanceCorrection
     */
    public function show(AttendanceCorrection $correction)
    {
        // 1. Load relationships for detail view
        return $correction->load(['employee.user', 'attendance', 'approver.user', 'employee.team.division', 'employee.position']);
    }

    /**
     * Store a new attendance correction request.
     *
     * @param  \App\Models\User  $user
     *
     * @throws Exception
     */
    public function store($user, array $data): AttendanceCorrection
    {
        return DB::transaction(function () use ($data) {
            // 0. Simple validation: Clock out cannot be before clock in
            $clockIn = Carbon::parse($data['clock_in_requested']);
            $clockOut = Carbon::parse($data['clock_out_requested']);
            if ($clockOut->lt($clockIn)) {
                throw new Exception('Requested clock out time cannot be earlier than clock in time.');
            }

            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/attendance_corrections', $filename);
            }

            // 1. Create the correction record
            $correction = AttendanceCorrection::create([
                'attendance_id' => $data['attendance_id'],
                'employee_id' => $data['employee_id'],
                'clock_in_requested' => $data['clock_in_requested'],
                'clock_out_requested' => $data['clock_out_requested'],
                'reason' => $data['reason'],
                'attachment' => $attachmentPath ?? null,
                'status' => ApprovalStatus::PENDING->value,
            ]);

            // 2. Send notification
            $correction->notifyCustom(
                title: 'New Attendance Correction Request',
                message: 'Your attendance correction request has been submitted.'
            );

            return $correction;
        });
    }

    /**
     * Update an existing attendance correction request.
     *
     * @param  \App\Models\User  $user
     *
     * @throws Exception
     */
    public function update(AttendanceCorrection $correction, array $data, $user): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $data, $user) {
            // 1. Validate if the request can still be modified
            if ($correction->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])) {
                throw new Exception('Processed correction cannot be modified.');
            }

            // 0. Simple validation: Clock out cannot be before clock in
            $clockIn = Carbon::parse($data['clock_in_requested'] ?? $correction->clock_in_requested);
            $clockOut = Carbon::parse($data['clock_out_requested'] ?? $correction->clock_out_requested);
            if ($clockOut->lt($clockIn)) {
                throw new Exception('Requested clock out time cannot be earlier than clock in time.');
            }

            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                if ($correction->attachment && Storage::exists($correction->attachment)) {
                    Storage::delete($correction->attachment);
                }
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $correction->attachment = $data['attachment']
                    ->storeAs('private/attendance_corrections', $filename);
            }

            // 2. Update the record
            $correction->update([
                'clock_in_requested' => $data['clock_in_requested'] ?? $correction->clock_in_requested,
                'clock_out_requested' => $data['clock_out_requested'] ?? $correction->clock_out_requested,
                'reason' => $data['reason'] ?? $correction->reason,
                'attachment' => $correction->attachment,
            ]);

            // 3. Send notification
            $correction->notifyCustom(
                title: 'Correction Request Updated',
                message: 'Your attendance correction request has been updated.'
            );

            return $correction;
        });
    }

    /**
     * Process approval or rejection of an attendance correction request.
     *
     * @param  \App\Models\User  $user
     * @return AttendanceCorrection
     *
     * @throws Exception
     */
    public function approve(AttendanceCorrection $correction, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($correction, $user, $approve, $note) {
            // 1. Ensure the request is still pending
            if ($correction->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Correction has already been processed.');
            }

            // 2. Permission check: Only direct manager or high-level roles can process
            $isManager = $correction->employee?->manager_id === optional($user->employee)->id;
            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
                throw new Exception('You do not have permission to approve this correction.');
            }

            // 3. Update the request status
            $correction->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approver_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            // 4. If approved, update the actual attendance record
            if ($approve) {
                $attendance = $correction->attendance;
                $employee = $correction->employee;

                $schedule = $this->timeValidator->getEmployeeScheduleTimes($employee, Carbon::parse($attendance->date));

                $requestedIn = $correction->clock_in_requested ? Carbon::parse($correction->clock_in_requested) : null;
                $requestedOut = $correction->clock_out_requested ? Carbon::parse($correction->clock_out_requested) : null;

                $lateMinutes = 0;
                if ($requestedIn && $requestedIn->greaterThan($schedule['work_start_time'])) {
                    $diffIn = $requestedIn->diffInMinutes($schedule['work_start_time']);
                    if ($diffIn > $schedule['late_tolerance_minutes']) {
                        $lateMinutes = $diffIn;
                    }
                }

                $earlyLeaveMinutes = 0;
                if ($requestedOut && $requestedOut->lessThan($schedule['work_end_time'])) {
                    $earlyLeaveMinutes = $requestedOut->diffInMinutes($schedule['work_end_time']);
                }

                $workMinutes = 0;
                if ($requestedIn && $requestedOut) {
                    $workMinutes = $requestedIn->diffInMinutes($requestedOut);
                }

                $newStatus = 'present';

                $attendance->update([
                    'clock_in' => $requestedIn,
                    'clock_out' => $requestedOut,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'work_minutes' => $workMinutes,
                    'is_corrected' => true,
                    'status' => $newStatus,
                ]);
            }
            // 5. Send notification to the employee
            $correction->notifyCustom(
                title: $approve ? 'Correction Approved' : 'Correction Rejected',
                message: $approve
                    ? "Your attendance correction has been approved by {$user->name}."
                    : "Your attendance correction has been rejected by {$user->name}."
            );

            return $correction;
        });
    }

      public function delete(AttendanceCorrection $attendanceCorrection): bool
    {
        return DB::transaction(function () use ($attendanceCorrection) {
            $attendanceCorrection->notifyCustom(
                title: 'AttendanceCorrection Request Deleted',
                message: "Employee {$attendanceCorrection->employee->user->name} has deleted their attendanceCorrection request for {$attendanceCorrection->attendance->date->toFormattedDateString()}."
            );

            return $attendanceCorrection->delete();
        });
    }
}
