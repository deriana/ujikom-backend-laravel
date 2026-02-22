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
use App\Models\LeaveType;
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

        // 1️⃣ ROLE AUDITOR/ADMIN (OWNER, DIRECTOR, HR, FINANCE)
        // Mereka bisa lihat SEMUA data untuk monitoring & laporan
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::FINANCE->value,
            UserRole::HR->value,
        ])) {
            // Tanpa filter query tambahan
        }

        // 2️⃣ MANAGER
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ EMPLOYEE
        else {
            $query->where('employee_id', $user->employee->id);
        }

        return LeaveResource::collection($query->latest()->get());
    }

    public function indexApprovals($user)
    {
        // 1. Inisialisasi Query Dasar
        $query = Leave::with([
            'employee.user',
            'leaveType',
            'approvals.approver.user',
        ])->pending();

        // 2. Cek Role Manager (Hanya melihat approval milik bawahannya)
        if ($user->hasRole(UserRole::MANAGER->value)) {
            // Manager WAJIB punya profil employee untuk identifikasi ID-nya di tabel approvals
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;

            // Filter: Hanya ambil leave di mana user ini terdaftar sebagai approver yang PENDING
            $query->whereHas('approvals', function ($q) use ($employeeId) {
                $q->where('approver_id', $employeeId)
                    ->where('status', ApprovalStatus::PENDING->value);
            });
        }

        // 3. Cek Role Tinggi / Admin (Akses Full / Tanpa Filter Approver ID)
        elseif ($user->hasAnyRole([
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
            UserRole::ADMIN->value,
        ])) {
            // Admin/HR bisa melihat semua yang statusnya 'pending' secara global
            // Tanpa filter whereHas('approvals')
        }

        // 4. Role lain (Employee biasa) tidak punya akses approval
        else {
            return collect();
        }

        return $query->latest()->get();
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
        return DB::transaction(function () use ($data, $user) {
            $employeeId = $data['employee_id'];
            $leaveTypeId = $data['leave_type_id'];
            $isHalfDay = $data['is_half_day'] ?? false;

            $start = Carbon::parse($data['date_start']);
            $end = Carbon::parse($data['date_end']);

            // $employee = $user->employee;
            // if (! $employee) {
            //     throw new \Exception('Employee profile not found.');
            // }

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
            // 1. Ambil data tipe cuti untuk cek apakah ini 'Infinite' atau tidak
            $leaveType = LeaveType::findOrFail($leaveTypeId);

            // 2. Logika pengecekan saldo
            if ($leaveType->is_unlimited) { // Pastikan Anda punya kolom boolean 'is_unlimited' di tabel leave_types
                $balance = null; // Tidak perlu cek saldo jika unlimited
            } else {
                // Hanya cari dan kunci saldo jika cuti ini BERKUOTA
                $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    throw new \Exception('Leave balance record not found for this leave type.');
                }

                if ($balance->remaining_days < $daysRequested) {
                    throw new \Exception('Insufficient leave balance. Remaining: '.$balance->remaining_days.' days.');
                }
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

            // --- SKENARIO A: DIRECTOR mengajukan cuti ---
            if ($requestorUser->hasRole(UserRole::DIRECTOR->value)) {

                // ambil employee owner
                $ownerEmployee = Employee::whereHas('user', function ($q) {
                    $q->role(UserRole::OWNER->value);
                })->first();

                // update status leave jadi APPROVED
                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                // buat record approval
                if ($ownerEmployee) {
                    $this->createApproval(
                        $leave->id,
                        $ownerEmployee->id,   // ✅ pakai employee_id
                        0,
                        ApprovalStatus::APPROVED->value
                    );
                }

                $this->finalizeLeave($leave, $daysRequested);
            }

            // --- SKENARIO B: MANAGER, HR, atau FINANCE mengajukan cuti ---
            elseif ($requestorUser->hasAnyRole([
                UserRole::MANAGER->value,
                UserRole::HR->value,
                UserRole::FINANCE->value,
            ])) {

                $directorEmployee = Employee::whereHas('user', function ($q) {
                    $q->role(UserRole::DIRECTOR->value);
                })->first();

                if ($directorEmployee) {
                    $this->createApproval(
                        $leave->id,
                        $directorEmployee->id, // ✅ pakai employee_id
                        0
                    );
                }
            }

            // --- SKENARIO C: STAFF (EMPLOYEE) mengajukan cuti ---
            else {

                if (! $employee->manager_id) {
                    throw new \Exception('Manager not assigned.');
                }

                // Level 0 → Manager
                $this->createApproval(
                    $leave->id,
                    $employee->manager_id, // ✅ pakai employee_id
                    0
                );

                // Level 1 → HR (tetap jangan dibuat sekarang kalau mau flow bersih)
                // bisa dibuat nanti saat manager approve
            }

            $leave->notifyCustom(
                title: 'New Leave Request',
                message: "Employee {$employee->user->name} has requested {$leave->leaveType->name} for {$daysRequested} day(s).",
            );

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

            $leave->notifyCustom(
                title: 'Leave Request Updated',
                message: "Employee {$leave->employee->user->name} has updated their leave request. New duration: {$newDuration} day(s).",
            );

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
        return DB::transaction(function () use ($approval, $user, $approve, $note) {

            if ($approval->status !== ApprovalStatus::PENDING->value) {
                throw new \Exception('Approval sudah diproses');
            }

            // 1. Update status record approval yang sedang diproses
            $approval->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_at' => now(),
                'note' => $note,
            ]);

            $leave = $approval->leave;
            $requestor = $leave->employee->user; // Pemilik cuti

            // ❌ SKENARIO: DITOLAK
            if (! $approve) {
                $leave->update(['approval_status' => ApprovalStatus::REJECTED->value]);

                // Kirim notif ke pembuat cuti bahwa cutinya ditolak
                $leave->notifyCustom(
                    title: 'Leave Request Rejected',
                    message: "Your leave request has been rejected by {$user->name}. Note: ".($note ?? '-'),
                    customUsers: collect([$requestor])
                );

                return $approval;
            }

            // ✅ SKENARIO: LANJUT KE HR (Level 1)
            // Jika yang approve adalah manager (level 0), buatkan record approval untuk HR
            if ($approval->level === 0 && $leave->employee->manager_id === $approval->approver_id) {
                $hrEmployee = Employee::whereHas('user', function ($q) {
                    $q->role(UserRole::HR->value);
                })->first();

                if ($hrEmployee) {
                    $this->createApproval(
                        $leave->id,
                        $hrEmployee->id,
                        1 // level HR
                    );

                    // Notif ke semua user HR bahwa ada cuti yang butuh approval level 2
                    $leave->notifyCustom(
                        title: 'New Leave Approval Needed',
                        message: "Leave request from {$requestor->name} has been approved by the Manager and requires HR verification.",
                        customUsers: \App\Models\User::role(UserRole::HR->value)->get()
                    );
                }
            }

            // 🏁 SKENARIO: FINAL APPROVAL
            // Cek apakah masih ada pending approval di tabel approvals
            $hasPending = $leave->approvals()
                ->where('status', ApprovalStatus::PENDING->value)
                ->exists();

            // Jika sudah tidak ada yang pending, berarti semua level sudah approve
            if (! $hasPending) {
                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                $days = $leave->duration ?? $this->calculateWorkDays(
                    $leave->date_start->toDateString(),
                    $leave->date_end->toDateString(),
                    $leave->is_half_day,
                    $this->workdayService
                );

                // Buat data realisasi untuk Payroll
                EmployeeLeave::create([
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start,
                    'end_date' => $leave->date_end,
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                ]);

                // Potong saldo jika cuti berkuota
                if (! $leave->leaveType->is_unlimited) {
                    $leaveYear = Carbon::parse($leave->date_start)->year;
                    $balance = EmployeeLeaveBalance::where([
                        'employee_id' => $leave->employee_id,
                        'leave_type_id' => $leave->leave_type_id,
                        'year' => $leaveYear,
                    ])->lockForUpdate()->first();

                    if (! $balance) {
                        $balance = EmployeeLeaveBalance::create([
                            'employee_id' => $leave->employee_id,
                            'leave_type_id' => $leave->leave_type_id,
                            'year' => $leaveYear,
                            'total_days' => $leave->leaveType->default_days ?? 0,
                            'used_days' => 0,
                        ]);
                    }
                    $balance->useDays($days);
                }

                // Notif ke pembuat cuti bahwa cutinya SUDAH FINAL disetujui
                $leave->notifyCustom(
                    title: 'Leave Request Approved',
                    message: "Congratulations! Your leave request for {$leave->date_start->format('d M Y')} has been fully approved.",
                    customUsers: collect([$requestor])
                );
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

            $leave->notifyCustom(
                title: 'Leave Request Cancelled',
                message: "Employee {$leave->employee->user->name} has cancelled their leave request."
            );

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
            'leave_id' => $leaveId,
            'approver_id' => $approverId,
            'level' => $level,
            'status' => $status ?? ApprovalStatus::PENDING->value,
            'approved_at' => $status === ApprovalStatus::APPROVED->value ? now() : null,
            'note' => $status === ApprovalStatus::APPROVED->value ? 'Auto-approved by system.' : null,
        ]);
    }
}
