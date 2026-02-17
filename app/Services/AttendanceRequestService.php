<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\ShiftTemplate;
use App\Models\WorkSchedule;
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceRequestService
{
    protected EmployeeShiftService $shiftService;

    protected EmployeeWorkScheduleService $workScheduleService;

    public function __construct(
        EmployeeShiftService $shiftService,
        EmployeeWorkScheduleService $workScheduleService
    ) {
        $this->shiftService = $shiftService;
        $this->workScheduleService = $workScheduleService;
    }

    /**
     * Display a listing of requests based on user role.
     */
    public function index($user)
    {
        $query = AttendanceRequest::with([
            'employee.user',
            'approver.user',
            'shiftTemplate',
            'workSchedule',
        ]);

        // 1️⃣ OWNER, DIRECTOR, HR, & ADMIN → Bisa lihat semua data
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
        ])) {
            // No filter
        }

        // 2️⃣ MANAGER → Milik sendiri + bawahan langsung
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ EMPLOYEE → Hanya milik sendiri
        else {
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    public function indexApproval($user)
    {
        if (! $user->employee) {
            return collect(); // kosong jika tidak punya employee
        }

        $employeeId = $user->employee->id;
        $query = AttendanceRequest::with([
            'employee.user',
            'approver.user',
            'shiftTemplate',
            'workSchedule',
        ])
            ->pending() // hanya status pending
            ->where('employee_id', '!=', $employeeId) // jangan ambil yang sendiri
            ->whereNull('approved_by_id'); // belum diapprove

        // Manager → hanya bisa approve bawahan, bukan manager/HR
        if ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee.user', function ($q) {
                $q->whereDoesntHave('roles', function ($q2) {
                    $q2->whereIn('name', ['manager', 'hr']); // pakai Spatie roles
                });
            })->whereHas('employee', function ($q) use ($employeeId) {
                $q->where('manager_id', $employeeId);
            });
        }
        // Director / HR / Finance / Owner → bisa approve semua
        elseif ($user->hasAnyRole([
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
        ])) {
            // tidak ada filter tambahan
        }
        // Employee biasa → tidak bisa approve
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    /**
     * Show details of a specific request.
     */
    public function show(AttendanceRequest $attendanceRequest)
    {
        return $attendanceRequest->load([
            'employee.user',
            'approver.user',
            'shiftTemplate',
            'workSchedule',
        ]);
    }

    /**
     * Create a new request (Inisiatif Employee).
     */
    public function store(array $data): AttendanceRequest
    {
        return DB::transaction(function () use ($data) {
            // 1. Ambil Employee (employee_id sudah di-merge dari FormRequest)
            $employee = Employee::findOrFail($data['employee_id']);

            $shiftTemplateId = null;
            $workScheduleId = null;

            if ($data['request_type'] === 'SHIFT') {
                if (empty($data['shift_template_uuid'])) {
                    throw new \Exception('Shift template is required for SHIFT request type.');
                }

                $shiftTemplateId = ShiftTemplate::where('uuid', $data['shift_template_uuid'])
                    ->value('id');

                if (! $shiftTemplateId) {
                    throw new \Exception('Shift template not found.');
                }
            }

            if ($data['request_type'] === 'WORK_MODE') {
                if (empty($data['work_schedule_uuid'])) {
                    throw new \Exception('Work schedule is required for WORK_MODE request type.');
                }

                $workScheduleId = WorkSchedule::where('uuid', $data['work_schedule_uuid'])
                    ->value('id');

                if (! $workScheduleId) {
                    throw new \Exception('Work schedule not found.');
                }
            }

            return AttendanceRequest::create([
                'employee_id' => $employee->id,
                'request_type' => $data['request_type'],
                'shift_template_id' => $shiftTemplateId,
                'work_schedule_id' => $workScheduleId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? $data['start_date'],
                'reason' => $data['reason'],
                'status' => ApprovalStatus::PENDING->value,
            ]);
        });
    }

    /**
     * Update a pending request.
     */
    public function update(AttendanceRequest $attendanceRequest, array $data, $user): AttendanceRequest
    {
        return DB::transaction(function () use ($attendanceRequest, $data, $user) {
            if ($attendanceRequest->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])) {
                throw new \Exception('Processed requests cannot be modified.');
            }

            $requestType = $data['request_type'] ?? $attendanceRequest->request_type;

            $shiftTemplateId = $attendanceRequest->shift_template_id;
            $workScheduleId = $attendanceRequest->work_schedules_id;

            if ($requestType === 'SHIFT') {
                if (isset($data['shift_template_uuid'])) {
                    $shiftTemplateId = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->value('id');
                    if (! $shiftTemplateId) {
                        throw new \Exception('Shift template not found.');
                    }
                }
                $workScheduleId = null;
            }

            if ($requestType === 'WORK_MODE') {
                if (isset($data['work_schedule_uuid'])) {
                    $workScheduleId = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->value('id');
                    if (! $workScheduleId) {
                        throw new \Exception('Work schedule not found.');
                    }
                }
                $shiftTemplateId = null;
            }

            $attendanceRequest->update([
                'request_type' => $requestType,
                'shift_template_id' => $shiftTemplateId,
                'work_schedules_id' => $workScheduleId,
                'start_date' => $data['start_date'] ?? $attendanceRequest->start_date,
                'end_date' => $data['end_date'] ?? ($data['start_date'] ?? $attendanceRequest->end_date),
                'reason' => $data['reason'] ?? $attendanceRequest->reason,
            ]);

            return $attendanceRequest;
        });
    }

    /**
     * Process approval and execute synchronization to main schedule tables.
     */
    public function approve(AttendanceRequest $attendanceRequest, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($attendanceRequest, $user, $approve, $note) {

            // 1️⃣ Pastikan masih pending
            if ($attendanceRequest->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Request has already been processed.');
            }

            // 2️⃣ Role & Manager Check
            $isManager = $attendanceRequest->employee?->manager_id === optional($user->employee)->id;
            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR])) {
                throw new Exception('You do not have permission to process this request.');
            }

            // 3️⃣ Jika disetujui, tembak Service terkait untuk Sinkronisasi Data
            if ($approve) {
                if ($attendanceRequest->request_type === 'SHIFT') {
                    $template = ShiftTemplate::findOrFail($attendanceRequest->shift_template_id);

                    $this->shiftService->store([
                        'employee_nik' => $attendanceRequest->employee->nik,
                        'shift_template_uuid' => $template->uuid,
                        'shift_date' => $attendanceRequest->start_date,
                    ]);
                } else {
                    $schedule = WorkSchedule::findOrFail($attendanceRequest->work_schedules_id);

                    $this->workScheduleService->store([
                        'employee_nik' => $attendanceRequest->employee->nik,
                        'work_schedule_uuid' => $schedule->uuid,
                        'start_date' => $attendanceRequest->start_date,
                        'end_date' => $attendanceRequest->end_date,
                    ]);
                }
            }

            // 4️⃣ Update status request
            $attendanceRequest->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_by_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            return $attendanceRequest;
        });
    }

    /**
     * Delete a request.
     */
    public function delete(AttendanceRequest $attendanceRequest): bool
    {
        return DB::transaction(function () use ($attendanceRequest) {
            // Optional: Tambahkan validasi hanya bisa hapus jika masih pending
            return $attendanceRequest->delete();
        });
    }
}
