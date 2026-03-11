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

/**
 * Class AttendanceRequestService
 *
 * Menangani logika bisnis untuk pengajuan kehadiran (shift atau mode kerja),
 * termasuk manajemen status persetujuan dan sinkronisasi ke jadwal kerja utama.
 */
class AttendanceRequestService
{
    protected EmployeeShiftService $shiftService; /**< Layanan untuk mengelola shift karyawan */

    protected EmployeeWorkScheduleService $workScheduleService; /**< Layanan untuk mengelola jadwal kerja rutin karyawan */

    /**
     * Membuat instance layanan pengajuan kehadiran baru.
     *
     * @param EmployeeShiftService $shiftService
     * @param EmployeeWorkScheduleService $workScheduleService
     */
    public function __construct(
        EmployeeShiftService $shiftService,
        EmployeeWorkScheduleService $workScheduleService
    ) {
        $this->shiftService = $shiftService;
        $this->workScheduleService = $workScheduleService;
    }

    /**
     * Mengambil daftar pengajuan kehadiran dengan filter berdasarkan peran pengguna.
     *
     * @param \App\Models\User $user Objek pengguna yang sedang login.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengajuan kehadiran.
     */
    public function index($user)
    {
        $query = AttendanceRequest::with([
            'employee.user',
            'approver.user',
            'shiftTemplate',
            'workSchedule',
        ]);

        // 1️⃣ OWNER, DIRECTOR, HR, & ADMIN → Can see all data
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
        ])) {
            // No filter
        }

        // 2️⃣ MANAGER → Own requests + direct subordinates
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ EMPLOYEE → Only own requests
        else {
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    /**
     * Mengambil daftar pengajuan kehadiran yang sedang menunggu persetujuan.
     *
     * @param \App\Models\User $user Objek pengguna yang sedang login.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengajuan yang tertunda.
     */
    public function indexApproval($user)
    {
        // 1. Initialize base query
        $query = AttendanceRequest::with([
            'employee.user',
            'approver.user',
            'shiftTemplate',
            'workSchedule',
        ])
            ->pending() // only pending status
            ->whereNull('approved_by_id'); // not yet approved

        // 2. Logic based on Role
        if ($user->hasRole(UserRole::MANAGER->value)) {
            // Manager MUST have an employee profile
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;
            $query->where('employee_id', '!=', $employeeId) // Cannot approve own request
                ->whereHas('employee.user', function ($q) {
                    $q->whereDoesntHave('roles', function ($q2) {
                        $q2->whereIn('name', ['manager', 'hr']);
                    });
                })
                ->whereHas('employee', function ($q) use ($employeeId) {
                    $q->where('manager_id', $employeeId);
                });
        }

        // 3. High-level Roles (Director / HR / Finance / Owner / Admin)
        elseif ($user->hasAnyRole([
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
            UserRole::ADMIN->value,
        ])) {
            // If the user has an employee profile, prevent them from seeing their own requests
            // If they don't (null), ignore this filter (can see all)
            if ($user->employee) {
                $query->where('employee_id', '!=', $user->employee->id);
            }
        }

        // 4. Fallback for other roles
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    /**
     * Menampilkan detail lengkap dari satu pengajuan kehadiran tertentu.
     *
     * @param AttendanceRequest $attendanceRequest Objek pengajuan kehadiran.
     * @return AttendanceRequest Objek pengajuan dengan relasi yang dimuat.
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
     * Menyimpan pengajuan kehadiran baru ke dalam database.
     *
     * @param array $data Data pengajuan (request_type, start_date, reason, dll).
     * @return AttendanceRequest Objek pengajuan yang berhasil dibuat.
     * @throws Exception Jika template shift atau jadwal kerja tidak ditemukan.
     */
    public function store(array $data): AttendanceRequest
    {
        return DB::transaction(function () use ($data) {
            // 1. Retrieve Employee (employee_id is merged from FormRequest)
            $employee = Employee::findOrFail($data['employee_id']);

            $shiftTemplateId = null;
            $workScheduleId = null;

            // 2. Handle SHIFT request type: validate and find template ID
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

            // 3. Handle WORK_MODE request type: validate and find schedule ID
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

            // 4. Create the attendance request record
            $attendanceRequest = AttendanceRequest::create([
                'employee_id' => $employee->id,
                'request_type' => $data['request_type'],
                'shift_template_id' => $shiftTemplateId,
                'work_schedule_id' => $workScheduleId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? $data['start_date'],
                'reason' => $data['reason'],
                'status' => ApprovalStatus::PENDING->value,
            ]);

            // 5. Send notification to relevant parties
            $attendanceRequest->notifyCustom(
                title: 'New Attendance Request',
                message: "Employee {$employee->user->name} has submitted a new attendance request for {$data['start_date']}."
            );

            return $attendanceRequest;
        });
    }

    /**
     * Memperbarui data pengajuan kehadiran yang masih dalam status tertunda.
     *
     * @param AttendanceRequest $attendanceRequest Objek pengajuan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param \App\Models\User $user Objek pengguna yang melakukan aksi.
     * @return AttendanceRequest Objek pengajuan setelah diperbarui.
     * @throws Exception Jika pengajuan sudah diproses dan pengguna bukan HR/Admin.
     */
    public function update(AttendanceRequest $attendanceRequest, array $data, $user): AttendanceRequest
    {
        return DB::transaction(function () use ($attendanceRequest, $data, $user) {
            // 1. Check if the request is still pending
            if (
                $attendanceRequest->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])
            ) {
                throw new \Exception('Processed requests cannot be modified.');
            }

            // 2. Determine request type and IDs
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

            // 3. Update the record
            $attendanceRequest->update([
                'request_type' => $requestType,
                'shift_template_id' => $shiftTemplateId,
                'work_schedules_id' => $workScheduleId,
                'start_date' => $data['start_date'] ?? $attendanceRequest->start_date,
                'end_date' => $data['end_date'] ?? ($data['start_date'] ?? $attendanceRequest->end_date),
                'reason' => $data['reason'] ?? $attendanceRequest->reason,
            ]);

            // 4. Send notification
            $attendanceRequest->notifyCustom(
                title: 'Attendance Request Updated',
                message: "Employee {$attendanceRequest->employee->user->name} has updated their attendance request."
            );

            return $attendanceRequest;
        });
    }

    /**
     * Memproses persetujuan pengajuan dan melakukan sinkronisasi ke tabel jadwal utama.
     *
     * @param AttendanceRequest $attendanceRequest Objek pengajuan.
     * @param \App\Models\User $user Objek pengguna penyetuju.
     * @param bool $approve Status persetujuan (true untuk setuju, false untuk tolak).
     * @param string|null $note Catatan dari penyetuju.
     * @return AttendanceRequest Objek pengajuan yang telah diperbarui.
     * @throws Exception Jika pengajuan sudah diproses atau pengguna tidak memiliki izin.
     */
    public function approve(AttendanceRequest $attendanceRequest, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($attendanceRequest, $user, $approve, $note) {

            // 1. Ensure the request is still pending
            if ($attendanceRequest->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Request has already been processed.');
            }

            // 2. Role & Manager Check
            $isManager = $attendanceRequest->employee?->manager_id === optional($user->employee)->id;
            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR])) {
                throw new Exception('You do not have permission to process this request.');
            }

            // 3. If approved, trigger the related Service for Data Synchronization
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

            $attendanceRequest->notifyCustom(
                title: $approve ? 'Attendance Request Approved' : 'Attendance Request Rejected',
                message: "Your attendance request for {$attendanceRequest->start_date} has been " . ($approve ? 'approved' : 'rejected') . "."
            );

            // 4. Update request status
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
     * Menghapus data pengajuan kehadiran.
     *
     * @param AttendanceRequest $attendanceRequest Objek pengajuan yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(AttendanceRequest $attendanceRequest): bool
    {
        return DB::transaction(function () use ($attendanceRequest) {
            $attendanceRequest->notifyCustom(
                title: 'Attendance Request Deleted',
                message: "Employee {$attendanceRequest->employee->user->name} has deleted their attendance request for {$attendanceRequest->start_date}."
            );
            // Optional: Add validation to only allow deletion if still pending
            return $attendanceRequest->delete();
        });
    }
}
