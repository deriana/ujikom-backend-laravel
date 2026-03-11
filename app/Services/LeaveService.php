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

/**
 * Class LeaveService
 *
 * Menangani logika bisnis untuk pengajuan cuti karyawan, termasuk perhitungan hari kerja,
 * validasi saldo cuti, manajemen persetujuan bertingkat, dan sinkronisasi saldo.
 */
class LeaveService
{
    protected WorkdayService $workdayService; /**< Layanan untuk validasi hari kerja dan hari libur */

    /**
     * Membuat instance layanan cuti baru.
     *
     * @param WorkdayService $workdayService
     */
    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    /**
     * Mengambil daftar pengajuan cuti dengan filter berdasarkan peran pengguna.
     *
     * @param \App\Models\User $user Objek pengguna yang sedang login.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Koleksi data pengajuan cuti.
     */
    public function index($user)
    {
        // 1. Initialize query with necessary relationships
        $query = Leave::with(['employee.user', 'leaveType', 'approvals.approver.user']);

        // 2. High-level Roles (Admin, Owner, Director, HR, Finance) -> Can see all data
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::FINANCE->value,
            UserRole::HR->value,
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

        return LeaveResource::collection($query->latest()->get());
    }

    /**
     * Mengambil riwayat cuti milik pengguna yang sedang terautentikasi.
     *
     * @param \App\Models\User $user Objek pengguna.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Koleksi data riwayat cuti terpaginasi.
     */
    public function myLeaves($user)
    {
        // 1. Retrieve paginated leave records for the specific employee
        $leaves = Leave::with(['leaveType', 'approvals'])
            ->where('employee_id', $user->employee->id)
            ->latest()
            ->paginate(10);

        return LeaveResource::collection($leaves);
    }

    /**
     * Mengambil daftar pengajuan cuti yang sedang menunggu persetujuan.
     *
     * @param \App\Models\User $user Objek pengguna penyetuju.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengajuan yang tertunda.
     */
    public function indexApprovals($user)
    {
        // 1. Initialize base query for pending requests
        $query = Leave::with([
            'employee.user',
            'leaveType',
            'approvals.approver.user',
        ])->pending();

        // 2. Manager Logic -> Can only see approvals assigned to them
        if ($user->hasRole(UserRole::MANAGER->value)) {
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;

            $query->whereHas('approvals', function ($q) use ($employeeId) {
                $q->where('approver_id', $employeeId)
                    ->where('status', ApprovalStatus::PENDING->value);
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
     * Menampilkan detail lengkap dari satu pengajuan cuti tertentu.
     *
     * @param Leave $leave Objek pengajuan cuti.
     * @return LeaveDetailResource Resource detail pengajuan cuti.
     */
    public function show(Leave $leave)
    {
        // 1. Load relationships for detail view
        $leave->load(['employee', 'leaveType', 'approvals', 'employeeLeave', 'employee.leaveBalances']);

        return new LeaveDetailResource($leave);
    }

    /**
     * Menyimpan pengajuan cuti baru ke dalam database.
     *
     * @param array $data Data pengajuan (employee_id, leave_type_id, date_start, dll).
     * @param \App\Models\User $user Objek pengguna yang membuat pengajuan.
     * @return Leave Objek cuti yang berhasil dibuat.
     * @throws Exception Jika hari kerja tidak ditemukan, saldo tidak cukup, atau manager tidak ada.
     */
    public function store(array $data, $user)
    {
        return DB::transaction(function () use ($data) {
            // 1. Extract basic request data
            $employeeId = $data['employee_id'];
            $leaveTypeId = $data['leave_type_id'];
            $isHalfDay = $data['is_half_day'] ?? false;

            $start = Carbon::parse($data['date_start']);
            $end = Carbon::parse($data['date_end']);

            // 2. Calculate actual working days requested
            $daysRequested = $this->calculateWorkDays(
                $start->toDateString(),
                $end->toDateString(),
                $isHalfDay,
                $this->workdayService
            );

            if ($daysRequested <= 0) {
                throw new \Exception('There are no working days in the selected date range.');
            }

            // 3. Validate leave balance
            $leaveType = LeaveType::findOrFail($leaveTypeId);

            $employee = Employee::findOrFail($employeeId);
            if ($leaveType->gender !== 'all' && $employee->gender !== $leaveType->gender) {
                throw new \Exception("This leave type is only available for {$leaveType->gender} employees.");
            }

            if ($leaveType->is_unlimited) {
                $balance = null;
            } else {
                $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $start->year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    throw new \Exception("Leave balance record not found for this leave type in year {$start->year}.");
                }

                if ($balance->remaining_days < $daysRequested) {
                    throw new \Exception('Insufficient leave balance. Remaining: '.$balance->remaining_days.' days.');
                }
            }

            // 4. Handle file attachment upload
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/leave_attachments', $filename);
            }

            $requestorUser = $employee->user;

            // 5. Create the leave record
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

            // 6. Multi-level Approval Logic based on Role

            // Scenario A: Director requests leave (Auto-approved by Owner)
            if ($requestorUser->hasRole(UserRole::DIRECTOR->value)) {
                $ownerEmployee = Employee::whereHas('user', function ($q) {
                    $q->role(UserRole::OWNER->value);
                })->first();

                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                if ($ownerEmployee) {
                    $this->createApproval(
                        $leave->id,
                        $ownerEmployee->id,
                        0,
                        ApprovalStatus::APPROVED->value
                    );
                }

                // Finalize immediately for Director
                $this->finalizeLeave($leave, $daysRequested);
            }
            // Scenario B: Manager, HR, or Finance requests leave (Requires Director approval)
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
            // Scenario C: Regular Staff requests leave (Requires Manager approval)
            else {
                if (! $employee->manager_id) {
                    throw new \Exception('Manager not assigned.');
                }

                $this->createApproval(
                    $leave->id,
                    $employee->manager_id,
                    0
                );
            }

            // 7. Send notification
            $leave->notifyCustom(
                title: 'New Leave Request',
                message: "Employee {$employee->user->name} has requested {$leave->leaveType->name} for {$daysRequested} day(s).",
            );

            return $leave;
        });
    }

    /**
     * Memperbarui data pengajuan cuti yang sudah ada.
     *
     * @param Leave $leave Objek pengajuan cuti yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param \App\Models\User $user Objek pengguna yang melakukan aksi.
     * @return Leave Objek cuti setelah diperbarui.
     * @throws Exception Jika pengajuan sudah diproses atau cuti sudah dimulai/berlalu.
     */
    public function update(Leave $leave, array $data, $user)
    {
        return DB::transaction(function () use ($leave, $data, $user) {
            // 1. Validate if the request can still be modified
            if (
                $leave->approval_status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR])
            ) {
                throw new Exception('Processed leave requests cannot be modified.');
            }

            // 2. Prevent modification of past or ongoing leave
            if (now()->gt($leave->date_start)) {
                throw new Exception('Leave that has already started or passed cannot be modified.');
            }

            $oldDuration = $leave->duration;

            // 3. Recalculate duration
            $newDuration = $this->calculateWorkDays(
                $data['date_start'],
                $data['date_end'],
                $data['is_half_day'] ?? false,
                $this->workdayService

            );

            // 4. Reset approval status if dates changed
            if (
                $leave->date_start != $data['date_start'] ||
                $leave->date_end != $data['date_end']
            ) {
                $leave->approval_status = ApprovalStatus::PENDING->value;
            }

            // 5. Handle attachment update and delete old file
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {

                if ($leave->attachment && Storage::exists($leave->attachment)) {
                    Storage::delete($leave->attachment);
                }

                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $leave->attachment = $data['attachment']
                    ->storeAs('private/leave_attachments', $filename);
            }

            // 6. Send notification
            $leave->notifyCustom(
                title: 'Leave Request Updated',
                message: "Employee {$leave->employee->user->name} has updated their leave request. New duration: {$newDuration} day(s).",
            );

            // 7. Update the record
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
     * Memproses persetujuan atau penolakan pengajuan cuti.
     *
     * @param LeaveApproval $approval Objek persetujuan cuti.
     * @param \App\Models\User $user Objek pengguna penyetuju.
     * @param bool $approve Status persetujuan (true untuk setuju, false untuk tolak).
     * @param string|null $note Catatan dari penyetuju.
     * @return LeaveApproval Objek persetujuan yang telah diperbarui.
     * @throws Exception Jika persetujuan sudah diproses sebelumnya.
     */
    public function approve(LeaveApproval $approval, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($approval, $user, $approve, $note) {
            // 1. Ensure the approval record is still pending
            if ($approval->status !== ApprovalStatus::PENDING->value) {
                throw new \Exception('Approval has already been processed.');
            }

            // 2. Update the specific approval record
            $approval->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_at' => now(),
                'note' => $note,
            ]);

            $leave = $approval->leave;
            $requestor = $leave->employee->user;

            // 3. Handle Rejection Scenario
            if (! $approve) {
                $leave->update(['approval_status' => ApprovalStatus::REJECTED->value]);

                $leave->notifyCustom(
                    title: 'Leave Request Rejected',
                    message: "Your leave request has been rejected by {$user->name}. Note: ".($note ?? '-'),
                    customUsers: collect([$requestor])
                );

                return $approval;
            }

            // 4. Handle Progression to HR (Level 1) if Manager (Level 0) approved
            if ($approval->level === 0 && $leave->employee->manager_id === $approval->approver_id) {
                $hrEmployee = Employee::whereHas('user', function ($q) {
                    $q->role(UserRole::HR->value);
                })->first();

                if ($hrEmployee) {
                    $this->createApproval(
                        $leave->id,
                        $hrEmployee->id,
                        1
                    );

                    $leave->notifyCustom(
                        title: 'New Leave Approval Needed',
                        message: "Leave request from {$requestor->name} has been approved by the Manager and requires HR verification.",
                        customUsers: \App\Models\User::role(UserRole::HR->value)->get()
                    );
                }
            }

            // 5. Final Approval Scenario: Check if all levels are completed
            $hasPending = $leave->approvals()
                ->where('status', ApprovalStatus::PENDING->value)
                ->exists();

            if (! $hasPending) {
                $leave->update(['approval_status' => ApprovalStatus::APPROVED->value]);

                $days = $leave->duration ?? $this->calculateWorkDays(
                    $leave->date_start->toDateString(),
                    $leave->date_end->toDateString(),
                    $leave->is_half_day,
                    $this->workdayService
                );

                // Create realization record for Payroll
                EmployeeLeave::create([
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start,
                    'end_date' => $leave->date_end,
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                ]);

                // Deduct balance if applicable
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

                // Send final approval notification
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
     * Menghapus atau membatalkan pengajuan cuti.
     *
     * @param Leave $leave Objek pengajuan cuti yang akan dihapus.
     * @param \App\Models\User $user Objek pengguna yang melakukan aksi.
     * @return bool True jika berhasil dihapus.
     * @throws Exception Jika status bukan pending atau pengguna tidak memiliki izin.
     */
    public function delete(Leave $leave, $user): bool
    {
        return DB::transaction(function () use ($leave, $user) {
            // 1. Ensure only pending leave can be deleted
            if ((string) $leave->approval_status !== (string) ApprovalStatus::PENDING->value) {
                throw new Exception('Only pending leave requests can be deleted.');
            }

            // 2. Permission check: Admin/HR or the owner of the request
            $userEmployeeId = optional($user->employee)->id;
            if (
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) &&
                $leave->employee_id !== $userEmployeeId
            ) {
                throw new Exception('You do not have permission to delete this leave request.');
            }

            // 3. Send notification
            $leave->notifyCustom(
                title: 'Leave Request Cancelled',
                message: "Employee {$leave->employee->user->name} has cancelled their leave request."
            );

            // 4. Delete associated approvals
            $leave->approvals()->delete();

            // 5. Delete the leave record
            $leave->delete();

            return true;
        });
    }

    /**
     * Menghitung jumlah hari kerja aktual di antara dua tanggal.
     *
     * @param string $start Tanggal mulai.
     * @param string $end Tanggal berakhir.
     * @param bool $isHalfDay Status apakah cuti setengah hari.
     * @param WorkdayService $workdayService Layanan validasi hari kerja.
     * @return float Jumlah hari kerja dalam format desimal.
     */
    private function calculateWorkDays(string $start, string $end, bool $isHalfDay, WorkdayService $workdayService): float
    {
        // 1. Handle half-day shortcut
        if ($isHalfDay) {
            return 0.5;
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        // 2. Validate date range
        if ($startDate->gt($endDate)) {
            return 0;
        }

        $days = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        // 3. Iterate through period and count valid workdays
        foreach ($period as $date) {
            if ($workdayService->isWorkday($date)) {
                $days++;
            }
        }

        return (float) $days;
    }

    /**
     * Memfinalisasi cuti: Membuat catatan realisasi dan memotong saldo cuti.
     *
     * @param Leave $leave Objek cuti yang disetujui.
     * @param float $days Jumlah hari yang diambil.
     */
    private function finalizeLeave($leave, $days)
    {
        // 1. Create realization record for Payroll
        EmployeeLeave::create([
            'employee_id' => $leave->employee_id,
            'leave_type_id' => $leave->leave_type_id,
            'start_date' => $leave->date_start,
            'end_date' => $leave->date_end,
            'days_taken' => $days,
            'status' => ApprovalStatus::APPROVED->value,
        ]);

        // 2. Deduct leave balance
        $balance = EmployeeLeaveBalance::where([
            'employee_id' => $leave->employee_id,
            'leave_type_id' => $leave->leave_type_id,
            'year' => \Carbon\Carbon::parse($leave->date_start)->year,
        ])->first();

        $isUnlimited = $leave->leaveType->is_unlimited ?? false;

        if (!$isUnlimited) {
            $balance = EmployeeLeaveBalance::where([
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'year' => \Carbon\Carbon::parse($leave->date_start)->year,
            ])->first();

            if ($balance) {
                $balance->useDays($days);
            }
        }
    }

    /**
     * Membuat catatan persetujuan untuk pengajuan cuti.
     *
     * @param int $leaveId ID pengajuan cuti.
     * @param int $approverId ID karyawan penyetuju.
     * @param int $level Tingkat persetujuan (0, 1, dst).
     * @param string|null $status Status awal persetujuan.
     * @return LeaveApproval Objek persetujuan yang dibuat.
     */
    private function createApproval($leaveId, $approverId, $level, $status = null)
    {
        // 1. Create the approval record with provided or default status
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
