<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Resources\LeaveDetailResource;
use App\Http\Resources\LeaveResource;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveService
{
    protected WorkdayService $workdayService;

    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    /**
     * List leave untuk index/table
     */
    public function index($user)
    {
        $query = Leave::with(['employee.user', 'leaveType', 'approvals.approver.user']);

        // 1️⃣ OWNER, DIRECTOR, HR, & FINANCE → Akses Full (Seluruh Perusahaan)
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // Tanpa filter tambahan agar bisa monitor seluruh cuti karyawan
        }

        // 2️⃣ MANAGER → Milik sendiri + Seluruh bawahan langsung
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ EMPLOYEE (Default) → Hanya milik sendiri
        else {
            $query->where('employee_id', $user->employee->id);
        }

        return LeaveResource::collection($query->latest()->get());
    }

    public function show(Leave $leave)
    {
        $leave->load(['employee', 'leaveType', 'approvals', 'employeeLeave', 'employee.leaveBalances']);

        return new LeaveDetailResource($leave);
    }

    /**
     * Buat leave baru
     */
    public function store(array $data, $user)
    {
        return DB::transaction(function () use ($data) {
            $employeeId = $data['employee_id'];
            $leaveTypeId = $data['leave_type_id'];
            $isHalfDay = $data['is_half_day'] ?? false;

            $start = Carbon::parse($data['date_start']);
            $end = Carbon::parse($data['date_end']);

            // 1. Hitung Hari Kerja (Skip Libur/Weekend)
            $daysRequested = $this->calculateWorkDays(
                $start->toDateString(),
                $end->toDateString(),
                $isHalfDay,
                $this->workdayService
            );

            if ($daysRequested <= 0) {
                throw new \Exception('There are no working days in the selected date range.');
            }

            // 🔒 Lock & Check Saldo
            $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new \Exception('Leave balance not found.');
            }
            if ($balance->remaining_days < $daysRequested) {
                throw new \Exception('Insufficient leave balance.');
            }

            // Attachment Logic
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/leave_attachments', $filename);
            }

            $employee = Employee::findOrFail($employeeId);
            $requestorUser = $employee->user;

            // 2. Simpan Data Leave
            $leave = Leave::create([
                'uuid' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'date_start' => $start,
                'date_end' => $end,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'approval_status' => ApprovalStatus::PENDING->value,
                'is_half_day' => $isHalfDay,
                'duration' => $daysRequested,
            ]);

            // 3. LOGIKA APPROVAL BERJENJANG (Dinamis sesuai Role)

            // --- SKENARIO A: DIRECTOR mengajukan cuti ---
            // --- DI DALAM FUNGSI STORE ---

            if ($requestorUser->hasRole(UserRole::DIRECTOR->value)) {
                $owner = User::role(UserRole::OWNER->value)->first();

                // 1. Update status Leave utama jadi APPROVED
                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                // 2. Buat record approval (Kirim parameter ke-4: ApprovalStatus::APPROVED)
                if ($owner) {
                    $this->createApproval(
                        $leave->id,
                        $owner->id,
                        0,
                        ApprovalStatus::APPROVED->value // <-- WAJIB TAMBAH INI
                    );
                }

                // 3. Eksekusi potong saldo & realisasi
                $this->finalizeLeave($leave, $daysRequested);
            }

            // --- SKENARIO B: MANAGER, HR, atau FINANCE mengajukan cuti ---
            elseif ($requestorUser->hasAnyRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                $director = User::role(UserRole::DIRECTOR->value)->first();
                if ($director) {
                    $this->createApproval($leave->id, $director->id, 0);
                }
            }

            // --- SKENARIO C: STAFF (EMPLOYEE) mengajukan cuti ---
            else {
                // Level 0: Manager Langsung (jika ada)
                if (! empty($employee->manager_id)) {
                    $managerUser = Employee::find($employee->manager_id)->user_id;
                    $this->createApproval($leave->id, $managerUser, 0);
                }

                // Level 1: HR (Sebagai verifikator akhir)
                $hrUser = User::role(UserRole::HR->value)->first();
                if ($hrUser) {
                    $this->createApproval($leave->id, $hrUser->id, 1);
                }
            }

            return $leave;
        });
    }

    /**
     * Update leave
     */
    public function update(Leave $leave, array $data, $user)
    {
        return DB::transaction(function () use ($leave, $data, $user) {

            Log::info($leave);

            if (
                $leave->approval_status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR])
            ) {
                throw new Exception('Processed leave requests cannot be modified.');
            }

            // 2️⃣ Jangan izinkan ubah leave yang sudah lewat
            if (now()->gt($leave->date_start)) {
                throw new Exception('Leave that has already started or passed cannot be modified.');
            }

            $oldDuration = $leave->duration;

            // 3️⃣ Hitung ulang durasi
            $newDuration = $this->calculateWorkDays(
                $data['date_start'],
                $data['date_end'],
                $data['is_half_day'] ?? false,
                $this->workdayService

            );

            // 4️⃣ Jika tanggal berubah → reset approval
            if (
                $leave->date_start != $data['date_start'] ||
                $leave->date_end != $data['date_end']
            ) {
                $leave->approval_status = ApprovalStatus::PENDING->value;
            }

            // 5️⃣ Handle attachment
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {

                if ($leave->attachment && Storage::exists($leave->attachment)) {
                    Storage::delete($leave->attachment);
                }

                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $leave->attachment = $data['attachment']
                    ->storeAs('private/leave_attachments', $filename);
            }

            // 6️⃣ Update leave
            $leave->update([
                'date_start' => $data['date_start'],
                'date_end' => $data['date_end'],
                'reason' => $data['reason'],
                'is_half_day' => $data['is_half_day'] ?? false,
                'duration' => $newDuration,
            ]);

            return $leave;
        });
    }

    /**
     * Approve / Reject leave
     */
    public function approve(LeaveApproval $approval, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($approval, $approve, $note) {
            if ($approval->status !== ApprovalStatus::PENDING->value) {
                throw new \Exception('Approval sudah diproses');
            }

            $approval->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_at' => now(),
                'note' => $note,
            ]);

            $leave = $approval->leave;

            if (! $approve) {
                $leave->update(['approval_status' => ApprovalStatus::REJECTED->value]);

                return $approval;
            }

            $hasPending = $leave->approvals()->where('status', ApprovalStatus::PENDING->value)->exists();

            if (! $hasPending) {
                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                $days = $leave->duration;

                if (is_null($days)) {
                    $days = $this->calculateWorkDays(
                        $leave->date_start->toDateString(),
                        $leave->date_end->toDateString(),
                        $leave->is_half_day,
                        $this->workdayService
                    );
                }

                EmployeeLeave::create([
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start,
                    'end_date' => $leave->date_end,
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                ]);

                $leaveYear = Carbon::parse($leave->date_start)->year;

                $balance = EmployeeLeaveBalance::where([
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year' => $leaveYear,
                ])
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    $balance = EmployeeLeaveBalance::create([
                        'employee_id' => $leave->employee_id,
                        'leave_type_id' => $leave->leave_type_id,
                        'year' => $leaveYear,
                        'total_days' => $leave->leaveType->default_days ?? 0,
                        'used_days' => 0,
                    ]);
                }

                // Eksekusi potong saldo
                $balance->useDays($days);
            }

            return $approval;
        });
    }

    /**
     * Delete / cancel leave
     */
    public function delete(Leave $leave, $user): bool
    {
        return DB::transaction(function () use ($leave, $user) {

            // Ensure only pending leave can be deleted
            if ((string) $leave->approval_status !== (string) ApprovalStatus::PENDING->value) {
                throw new Exception('Only pending leave requests can be deleted.');
            }

            // Check access rights: admin/HR or leave owner
            $userEmployeeId = optional($user->employee)->id;
            if (
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) &&
                $leave->employee_id !== $userEmployeeId
            ) {
                throw new Exception('You do not have permission to delete this leave request.');
            }

            // Delete all related approvals
            $leave->approvals()->delete();

            // Delete leave
            $leave->delete();

            return true;
        });
    }

    private function calculateWorkDays(string $start, string $end, bool $isHalfDay, WorkdayService $workdayService): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($startDate->gt($endDate)) {
            return 0;
        }

        $days = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            if ($workdayService->isWorkday($date)) {
                $days++;
            }
        }

        return (float) $days;
    }

    /**
     * Finalisasi cuti: Buat data realisasi dan potong saldo
     */
    private function finalizeLeave($leave, $days)
    {
        // Simpan ke data realisasi untuk kebutuhan Payroll
        EmployeeLeave::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'employee_id' => $leave->employee_id,
            'leave_type_id' => $leave->leave_type_id,
            'start_date' => $leave->date_start,
            'end_date' => $leave->date_end,
            'days_taken' => $days,
            'status' => ApprovalStatus::APPROVED->value,
        ]);

        // Potong Saldo
        $balance = EmployeeLeaveBalance::where([
            'employee_id' => $leave->employee_id,
            'leave_type_id' => $leave->leave_type_id,
            'year' => \Carbon\Carbon::parse($leave->date_start)->year,
        ])->first();

        if ($balance) {
            $balance->useDays($days);
        }
    }

    /**
     * Buat data approval dengan status dinamis
     */
    private function createApproval($leaveId, $approverId, $level, $status = null)
    {
        return LeaveApproval::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'leave_id' => $leaveId,
            'approver_id' => $approverId,
            'level' => $level,
            'status' => $status ?? ApprovalStatus::PENDING->value,
            'approved_at' => $status === ApprovalStatus::APPROVED->value ? now() : null,
            'note' => $status === ApprovalStatus::APPROVED->value ? 'Auto-approved by system.' : null,
        ]);
    }
}
