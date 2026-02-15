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
        $query = Leave::with(['employee', 'leaveType', 'approvals']);

        // 1. ROLE: EMPLOYEE (Hanya lihat milik sendiri)
        if ($user->hasRole(UserRole::EMPLOYEE)) {
            $query->where('employee_id', $user->employee->id);
        }

        // 2. ROLE: MANAGER (Milik sendiri + Bawahan langsung)
        elseif ($user->hasRole(UserRole::MANAGER)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id) // Milik sendiri
                    ->orWhereHas('employee', fn ($sq) => $sq->where('manager_id', $user->employee->id)); // Milik bawahan
            });
        }

        // 3. ROLE: HR (Lihat semua yang sudah 'siap' di-approve HR)
        elseif ($user->hasRole(UserRole::HR)) {
            $query->where(function ($q) {
                // HR hanya melihat yang:
                // A. Tidak punya level 0 (Misal: Pengaju adalah Manager)
                // B. ATAU Level 0-nya sudah APPROVED
                $q->whereDoesntHave('approvals', function ($sq) {
                    $sq->where('level', 0);
                })
                    ->orWhereHas('approvals', function ($sq) {
                        $sq->where('level', 0)
                            ->where('status', ApprovalStatus::APPROVED->value);
                    });
            });
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

            $daysRequested = $this->calculateWorkDays(
                $start->toDateString(),
                $end->toDateString(),
                $isHalfDay,
                $this->workdayService
            );

            if ($daysRequested <= 0) {
                throw new \Exception('Tidak ada hari kerja pada rentang tanggal yang dipilih (Weekend/Libur).');
            }

            // 🔒 Lock & Check Saldo
            $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new \Exception('Saldo cuti tidak ditemukan');
            }
            if ($balance->remaining_days < $daysRequested) {
                throw new \Exception('Saldo cuti tidak mencukupi');
            }

            // Attachment Logic
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/leave_attachments', $filename);
            }

            $employee = Employee::findOrFail($employeeId);

            // 1. Simpan Data Leave
            $leave = Leave::create([
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

            // 2. LOGIKA APPROVAL DINAMIS

            // --- LEVEL 0: Manager (Hanya jika employee punya manager_id) ---
            if (! empty($employee->manager_id)) {
                LeaveApproval::create([
                    'leave_id' => $leave->id,
                    'approver_id' => $employee->manager_id,
                    'level' => 0,
                    'status' => ApprovalStatus::PENDING->value,
                ]);
            }

            // --- LEVEL 1: HR (Semua orang harus lewat HR) ---
            // Cari User yang memiliki role HR.
            // Contoh: cari user pertama yang punya role 'hr'

            LeaveApproval::create([
                'leave_id' => $leave->id,
                'approver_id' => null, // ID HR sistem
                'level' => 1,
                'status' => ApprovalStatus::PENDING->value,
            ]);

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
                throw new Exception('Leave yang sudah diproses tidak dapat diubah.');
            }

            // 2️⃣ Jangan izinkan ubah leave yang sudah lewat
            if (now()->gt($leave->date_start)) {
                throw new Exception('Leave yang sudah berjalan atau lewat tidak dapat diubah.');
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

            // 🔒 Pastikan hanya leave pending
            if ((string) $leave->approval_status !== (string) ApprovalStatus::PENDING->value) {
                throw new Exception('Hanya leave pending yang bisa dihapus.');
            }

            // 🔒 Cek hak akses: admin/HR atau owner leave
            $userEmployeeId = optional($user->employee)->id;
            if (
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) &&
                $leave->employee_id !== $userEmployeeId
            ) {
                throw new Exception('Anda tidak memiliki izin untuk menghapus leave ini.');
            }

            // 🔹 Hapus semua approval terkait
            $leave->approvals()->delete();

            // 🔹 Hapus leave
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
}
