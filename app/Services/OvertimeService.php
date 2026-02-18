<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class OvertimeService
{
    /**
     * Ambil semua lembur yang diajukan sesuai role
     */
    public function index($user)
    {
        $query = Overtime::with(['employee.user', 'attendance', 'manager.user']);

        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // Bisa lihat semua
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $employeeId = $user->employee->id;
            $query->where(function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId)
                    ->orWhereHas('employee', fn ($sq) => $sq->where('manager_id', $employeeId));
            });
        } else {
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    /**
     * Ambil lembur yang perlu diapprove
     */
    public function indexApproval($user)
    {
        // 1. Inisialisasi query dasar
        $query = Overtime::with(['employee.user', 'attendance', 'manager.user'])
            ->pending()
            ->whereNull('approved_by_id');

        // 2. Cek Role Admin/HR/Director/Owner (Akses Full)
        if ($user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
            // Biarkan query tanpa filter tambahan (ambil semua)
        }

        // 3. Cek Role Manager (Hanya bawahan)
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            // Manager WAJIB punya relasi employee untuk tahu siapa bawahannya
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $employeeId));
        }

        // 4. Jika bukan siapa-siapa (misal: Employee biasa)
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    public function show(Overtime $overtime)
    {
        return $overtime->load(['employee.user', 'attendance', 'manager.user', 'employee.team.division']);
    }

    /**
     * Ajukan lembur
     */
    public function store($user, array $data): Overtime
    {
        $attendance = Attendance::findOrFail($data['attendance_id']);

        $this->validateOvertimeEligibility($attendance);

        return DB::transaction(function () use ($data) {
            return Overtime::create([
                'attendance_id' => $data['attendance_id'],
                'employee_id' => $data['employee_id'],
                'reason' => $data['reason'],
                'status' => ApprovalStatus::PENDING->value,
            ]);
        });
    }

    /**
     * Update lembur (misal alasan atau durasi manual)
     */
    public function update(Overtime $overtime, array $data, $user): Overtime
    {
        return DB::transaction(function () use ($overtime, $data, $user) {
            if ($overtime->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])) {
                throw new Exception('Processed overtime cannot be modified.');
            }

            $overtime->update([
                'reason' => $data['reason'] ?? $overtime->reason,
            ]);

            return $overtime;
        });
    }

    /**
     * Approve / Reject lembur
     */
    public function approve(Overtime $overtime, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($overtime, $user, $approve, $note) {
            if ($overtime->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Overtime sudah diproses.');
            }

            $isManager = $overtime->employee?->manager_id === optional($user->employee)->id;

            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
                throw new Exception('Tidak punya akses untuk approve lembur ini.');
            }

            $overtime->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_by_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            return $overtime;
        });
    }

    /**
     * Update durasi lembur saat clock out
     */
    public function updateDurationAfterClockOut(Attendance $attendance)
    {
        if (! $attendance->clock_out) {
            return;
        }

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

            // 🔁 Fallback ke default setting
            $attendanceSetting = Setting::where('key', 'attendance')->first();

            if (! $attendanceSetting || ! isset($attendanceSetting->values['work_end_time'])) {
                throw new Exception('Default attendance setting not configured.');
            }

            $shiftEnd = Carbon::parse($attendance->date)
                ->setTimeFromTimeString($attendanceSetting->values['work_end_time']);
        }

        // Hitung overtime hanya jika clock_out > shift_end
        if ($attendance->clock_out->lte($shiftEnd)) {
            return;
        }

        $durationMinutes = $shiftEnd->diffInMinutes($attendance->clock_out);

        $overtime = Overtime::where('attendance_id', $attendance->id)
            ->pending()
            ->first();

        if ($overtime) {
            $overtime->update([
                'duration_minutes' => $durationMinutes,
            ]);
        } else {
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
     * Hapus lembur
     */
    public function delete(Overtime $overtime): bool
    {
        return DB::transaction(fn () => $overtime->delete());
    }

    /**
     * Validasi pengajuan lembur
     */
    private function validateOvertimeEligibility(Attendance $attendance): void
    {
        if (! $attendance->clock_in) {
            throw new Exception('You have not clocked in yet.');
        }

        if ($attendance->clock_out) {
            throw new Exception('Cannot request overtime after clocking out.');
        }

        if (Overtime::where('attendance_id', $attendance->id)->exists()) {
            throw new Exception('An overtime request has already been submitted for this attendance.');
        }
    }
}
