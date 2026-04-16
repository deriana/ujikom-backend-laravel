<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Setting;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Class OvertimeService
 *
 * Menangani logika bisnis untuk pengajuan lembur (overtime) karyawan,
 * termasuk validasi kelayakan, perhitungan durasi otomatis, dan manajemen persetujuan.
 */
class OvertimeService
{
    /**
     * Mengambil semua data lembur dengan filter berdasarkan peran pengguna.
     *
     * @param \App\Models\User $user Objek pengguna yang sedang login.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data lembur.
     */
    public function index($user, ?array $filters = null)
    {
        // 1. Initialize query with necessary relationships
        $query = Overtime::with(['employee.user', 'attendance', 'manager.user']);

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

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereHas('attendance', function ($q) use ($filters) {
                $q->whereBetween('date', [
                    Carbon::parse($filters['start_date'])->toDateString(),
                    Carbon::parse($filters['end_date'])->toDateString(),
                ]);
            });
        } else {
            $query->whereHas('attendance', function ($q) {
                $q->whereDate('date', Carbon::today());
            });
        }

        return $query->latest()->get();
    }

    /**
     * Mengambil daftar pengajuan lembur yang sedang menunggu persetujuan.
     *
     * @param \App\Models\User $user Objek pengguna penyetuju.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengajuan yang tertunda.
     */
    public function indexApproval($user)
    {
        // 1. Initialize base query for pending requests
        $query = Overtime::with(['employee.user', 'attendance', 'manager.user'])
            ->pending()
            ->whereNull('approved_by_id');

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
     * Menampilkan detail lengkap dari satu pengajuan lembur tertentu.
     *
     * @param Overtime $overtime Objek pengajuan lembur.
     * @return Overtime Objek lembur dengan relasi yang dimuat.
     */
    public function show(Overtime $overtime)
    {
        // 1. Load relationships for detail view
        return $overtime->load(['employee.user', 'attendance', 'manager.user', 'employee.team.division']);
    }

    /**
     * Menyimpan pengajuan lembur baru ke dalam database.
     *
     * @param \App\Models\User $user Objek pengguna yang membuat pengajuan.
     * @param array $data Data pengajuan (attendance_id, employee_id, reason).
     * @return Overtime Objek lembur yang berhasil dibuat.
     * @throws DomainException Jika karyawan belum layak mengajukan lembur.
     */
    public function store($user, array $data): Overtime
    {
        // 1. Find attendance and validate eligibility
        $attendance = Attendance::findOrFail($data['attendance_id']);
        $this->validateOvertimeEligibility($attendance);

        return DB::transaction(function () use ($data) {
            // 2. Create the overtime record
            $overtime = Overtime::create([
                'attendance_id' => $data['attendance_id'],
                'employee_id' => $data['employee_id'],
                'reason' => $data['reason'],
                'status' => ApprovalStatus::PENDING->value,
            ]);

            // 3. Send notification
            $overtime->notifyCustom(
                title: 'New Overtime Request Created',
                message: 'Your overtime request has been submitted.'
            );

            return $overtime;
        });
    }

    /**
     * Memperbarui data pengajuan lembur yang sudah ada.
     *
     * @param Overtime $overtime Objek pengajuan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param \App\Models\User $user Objek pengguna yang melakukan aksi.
     * @return Overtime Objek lembur setelah diperbarui.
     * @throws DomainException Jika pengajuan sudah diproses dan pengguna bukan HR/Admin.
     */
    public function update(Overtime $overtime, array $data, $user): Overtime
    {
        return DB::transaction(function () use ($overtime, $data, $user) {
            // 1. Validate if the request can still be modified
            if ($overtime->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])) {
                throw new \DomainException('Processed overtime cannot be modified.');
            }

            // 2. Update the record
            $overtime->update([
                'reason' => $data['reason'] ?? $overtime->reason,
            ]);

            // 3. Send notification
            $overtime->notifyCustom(
                title: 'Overtime Request Updated',
                message: 'Your overtime request has been updated.'
            );

            return $overtime;
        });
    }

    /**
     * Memproses persetujuan atau penolakan pengajuan lembur.
     *
     * @param Overtime $overtime Objek pengajuan lembur.
     * @param \App\Models\User $user Objek pengguna penyetuju.
     * @param bool $approve Status persetujuan (true untuk setuju, false untuk tolak).
     * @param string|null $note Catatan dari penyetuju.
     * @return Overtime Objek lembur yang telah diperbarui.
     * @throws DomainException Jika pengajuan sudah diproses atau pengguna tidak memiliki izin.
     */
    public function approve(Overtime $overtime, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($overtime, $user, $approve, $note) {
            // 1. Ensure the request is still pending
            if ($overtime->status !== ApprovalStatus::PENDING->value) {
                throw new \DomainException('Overtime has already been processed.');
            }

            // 2. Permission check: Only direct manager or high-level roles can process
            $isManager = $overtime->employee?->manager_id === optional($user->employee)->id;
            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
                throw new \DomainException('You do not have permission to approve this overtime.');
            }

            // 3. Update the request status and approval details
            $overtime->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_by_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            // 4. Send notification to the employee
            $overtime->notifyCustom(
                title: $approve ? 'Overtime Approved' : 'Overtime Rejected',
                message: $approve
                    ? "Your overtime request has been approved by {$user->name}."
                    : "Your overtime request has been rejected by {$user->name}."
            );

            return $overtime;
        });
    }

    /**
     * Memperbarui durasi lembur secara otomatis setelah karyawan melakukan clock-out.
     *
     * @param Attendance $attendance Objek kehadiran yang baru saja di-update clock-out-nya.
     * @return void
     * @throws DomainException Jika pengaturan kehadiran default tidak ditemukan.
     */
    public function updateDurationAfterClockOut(Attendance $attendance)
    {
        // 1. Ensure employee has clocked out
        if (! $attendance->clock_out) {
            return;
        }

        // 2. Determine shift end time from template or settings
        $employeeShift = $attendance->employee
            ->shifts()
            ->where('shift_date', $attendance->date)
            ->with('shiftTemplate')
            ->first();

        if ($employeeShift && $employeeShift->shiftTemplate) {

            $shiftEnd = Carbon::parse($employeeShift->shiftTemplate->end_time);

            if ($employeeShift->shiftTemplate->cross_day) {
                $shiftEnd->addDay();
            }

        } else {
            // Fallback to default attendance settings
            $attendanceSetting = Setting::where('key', 'attendance')->first();

            if (! $attendanceSetting || ! isset($attendanceSetting->values['work_end_time'])) {
                throw new \DomainException('Default attendance setting not configured.');
            }

            $shiftEnd = Carbon::parse($attendance->date)
                ->setTimeFromTimeString($attendanceSetting->values['work_end_time']);
        }

        // 3. Calculate overtime only if clock_out is after shift_end
        if ($attendance->clock_out->lte($shiftEnd)) {
            return;
        }

        $durationMinutes = $shiftEnd->diffInMinutes($attendance->clock_out);

        // 4. Find existing pending overtime or create a new auto-generated record
        $overtime = Overtime::where('attendance_id', $attendance->id)
            ->pending()
            ->first();

        if ($overtime) {
            // Update existing record with calculated duration
            $overtime->update([
                'duration_minutes' => $durationMinutes,
            ]);
        } else {
            // Create new auto-generated overtime record
            Overtime::create([
                'employee_id' => $attendance->employee_id,
                'attendance_id' => $attendance->id,
                'duration_minutes' => $durationMinutes,
                'status' => ApprovalStatus::PENDING->value,
                'reason' => 'Auto generated after clock out',
            ]);
        }
    }

    /**
     * Menghapus data pengajuan lembur.
     *
     * @param Overtime $overtime Objek pengajuan lembur yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(Overtime $overtime): bool
    {
        return DB::transaction(function () use ($overtime) {
            // 1. Send notification before deletion
            $overtime->notifyCustom(
                title: 'Overtime Request Deleted',
                message: "Employee {$overtime->employee->user->name} has deleted their overtime request for {$overtime->attendance->date->toFormattedDateString()}."
            );

            return $overtime->delete();
        });
    }

    /**
     * Memvalidasi apakah karyawan layak untuk mengajukan lembur.
     *
     * @param Attendance $attendance Objek kehadiran terkait.
     * @throws DomainException Jika belum clock-in, sudah clock-out, atau sudah pernah mengajukan.
     */
    private function validateOvertimeEligibility(Attendance $attendance): void
    {
        // 1. Check if the employee has clocked in
        if (! $attendance->clock_in) {
            throw new \DomainException('You have not clocked in yet.');
        }

        // 2. Ensure the employee hasn't already clocked out
        if ($attendance->clock_out) {
            throw new \DomainException('Cannot request overtime after clocking out.');
        }

        // 3. Prevent duplicate requests for the same attendance record
        if (Overtime::where('attendance_id', $attendance->id)->exists()) {
            throw new \DomainException('An overtime request has already been submitted for this attendance.');
        }
    }
}
