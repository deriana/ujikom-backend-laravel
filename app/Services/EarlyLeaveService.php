<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Resources\EarlyLeaveDetailResource;
use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EarlyLeaveService
{
    /**
     * Get a list of early leave requests with role-based filtering.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index($user)
    {
        // 1. Initialize query with necessary relationships
        $query = EarlyLeave::with([
            'employee.user',
            'attendance',
            'employee.manager',
        ]);

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
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 4. Employee -> Can only see their own requests
        else {
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    /**
     * Get a list of pending early leave requests that require approval.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function indexApproval($user)
    {
        // 1. Initialize base query for pending requests
        $query = EarlyLeave::with([
            'employee.user',
            'attendance',
            'approver.user',
        ])
            ->pending()
            ->whereNull('approved_by_id');

        // 2. Manager Logic -> Can only approve direct subordinates who are not managers/HR
        if ($user->hasRole(UserRole::MANAGER->value)) {
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;

            $query->whereHas('employee.user', function ($q) {
                $q->whereDoesntHave('roles', function ($q2) {
                    $q2->whereIn('name', ['manager', 'hr']);
                });
            })->whereHas('employee', function ($q) use ($employeeId) {
                $q->where('manager_id', $employeeId);
            });
        }

        // 3. High-level Roles -> Full access to all pending requests
        elseif ($user->hasAnyRole([
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
            UserRole::ADMIN->value,
        ])) {
            // No additional filter
        }

        // 4. Fallback for other roles
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    /**
     * Show details of a specific early leave request.
     *
     * @param EarlyLeave $earlyLeave
     * @return EarlyLeaveDetailResource
     */
    public function show(EarlyLeave $earlyLeave)
    {
        // 1. Load relationships for detail view
        $earlyLeave->load([
            'attendance',
            'employee.user',
            'approver.user',
        ]);

        return new EarlyLeaveDetailResource($earlyLeave);
    }

    /**
     * Store a new early leave request.
     *
     * @param array $data
     * @return EarlyLeave
     * @throws Exception
     */
    public function store(array $data): EarlyLeave
    {
        return DB::transaction(function () use ($data) {
            // 1. Retrieve employee and current date
            $employee = Employee::findOrFail($data['employee_id']);
            $today = Carbon::today();

            // 2. Find today's attendance record
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->firstOrFail();

            // 3. Validate if the employee is eligible to request early leave
            $this->validateEarlyLeaveEligibility($attendance);

            // 4. Handle file attachment upload
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $attachmentPath = $data['attachment']
                    ->storeAs('private/early_leave_attachments', $filename);
            }

            // 5. Create the early leave record
            $earlyLeave = EarlyLeave::create([
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'status' => ApprovalStatus::PENDING->value,
            ]);

            // 6. Send notification
            $earlyLeave->notifyCustom(
                title: 'New Early Leave Request',
                message: "Employee {$employee->user->name} has requested early leave for today.",
            );

            return $earlyLeave;
        });
    }

    /**
     * Update an existing early leave request.
     *
     * @param EarlyLeave $earlyLeave
     * @param array $data
     * @param \App\Models\User $user
     * @return EarlyLeave
     * @throws Exception
     */
    public function update(EarlyLeave $earlyLeave, array $data, $user): EarlyLeave
    {
        return DB::transaction(function () use ($earlyLeave, $data, $user) {
            // 1. Validate if the request can still be modified
            if (
                $earlyLeave->status !== ApprovalStatus::PENDING->value && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])
            ) {
                throw new Exception('Processed early leave requests cannot be modified.');
            }

            // 2. Ensure the request is for the current day
            if ($earlyLeave->attendance?->date->isPast() &&
                ! $earlyLeave->attendance->date->isToday()) {
                throw new Exception('Early leave requests can only be modified on the same day.');
            }

            // 3. Handle attachment update and delete old file
            $attachmentPath = $earlyLeave->attachment;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                if ($earlyLeave->attachment && Storage::exists($earlyLeave->attachment)) {
                    Storage::delete($earlyLeave->attachment);
                }

                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $earlyLeave->attachment = $data['attachment']
                    ->storeAs('private/early_leave_attachments', $filename);
            }

            // 4. Send notification
            $earlyLeave->notifyCustom(
                title: 'Early Leave Request Updated',
                message: "Employee {$earlyLeave->employee->user->name} has updated their early leave request.",
            );

            // 5. Update the record
            $earlyLeave->update([
                'reason' => $data['reason'] ?? $earlyLeave->reason,
                'attachment' => $attachmentPath,
            ]);

            return $earlyLeave;
        });
    }

    /**
     * Process approval or rejection of an early leave request.
     *
     * @param EarlyLeave $earlyLeave
     * @param \App\Models\User $user
     * @param bool $approve
     * @param string|null $note
     * @return EarlyLeave
     * @throws Exception
     */
    public function approve(EarlyLeave $earlyLeave, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($earlyLeave, $user, $approve, $note) {
            // 1. Ensure the request is still pending
            if ($earlyLeave->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Early leave request has already been processed.');
            }

            // 2. Permission check: Only direct manager or high-level roles can process
            $isManager = $earlyLeave->employee?->manager_id === optional($user->employee)->id;

            if (
                ! $isManager &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR])
            ) {
                throw new Exception('You do not have permission to process this early leave request.');
            }

            // 3. Send notification to the employee
            $earlyLeave->notifyCustom(
                title: $approve ? 'Early Leave Approved' : 'Early Leave Rejected',
                message: "Your early leave request for {$earlyLeave->attendance->date->toFormattedDateString()} has been ".($approve ? 'approved' : 'rejected').".",
            );

            // 4. Update the request status and approval details
            $earlyLeave->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_by_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            return $earlyLeave;
        });
    }

    /**
     * Delete an early leave request.
     *
     * @param EarlyLeave $earlyLeave
     * @param \App\Models\User $user
     * @return bool
     */
    public function delete(EarlyLeave $earlyLeave, $user): bool
    {
        return DB::transaction(function () use ($earlyLeave) {
            // 1. Delete associated attachment file
            if ($earlyLeave->attachment) {
                Storage::delete($earlyLeave->attachment);
            }

            // 2. Send notification
            $earlyLeave->notifyCustom(
                title: 'Early Leave Request Deleted',
                message: "Employee {$earlyLeave->employee->user->name} has deleted their early leave request for {$earlyLeave->attendance->date->toFormattedDateString()}.",
            );

            $earlyLeave->delete();

            return true;
        });
    }

    /**
     * Validate if the employee is eligible to submit an early leave request.
     *
     * @param Attendance $attendance
     * @throws Exception
     */
    private function validateEarlyLeaveEligibility(Attendance $attendance): void
    {
        // 1. Check if the employee has clocked in
        if (! $attendance->clock_in) {
            throw new Exception('You have not clocked in yet.');
        }

        // 2. Ensure the employee hasn't already clocked out
        if ($attendance->clock_out) {
            throw new Exception('Early leave cannot be requested after clocking out.');
        }

        // 3. Prevent duplicate requests for the same attendance record
        if (EarlyLeave::where('attendance_id', $attendance->id)->exists()) {
            throw new Exception('An early leave request has already been submitted for this attendance.');
        }
    }
}
