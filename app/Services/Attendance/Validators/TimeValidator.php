<?php

namespace App\Services\Attendance\Validators;

use App\Enums\ApprovalStatus;
use App\Exceptions\Attendance\AttendanceException;
use App\Exceptions\Attendance\TimeValidationException;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Setting;
use App\Services\WorkdayService;
use Carbon\Carbon;

/**
 * Class TimeValidator
 *
 * Menangani validasi waktu kehadiran, termasuk pengecekan hari kerja, jendela waktu masuk/pulang,
 * serta pengambilan jadwal kerja karyawan dari berbagai tingkatan (shift, jadwal rutin, atau default).
 */
class TimeValidator
{
    /**
     * Instance layanan hari kerja.
     */
    protected WorkdayService $workdayService; /**< Layanan untuk mengecek validitas hari kerja */

    /**
     * Membuat instance validator baru.
     *
     * @param WorkdayService $workdayService
     */
    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    /**
     * Memvalidasi apakah tanggal yang diberikan adalah hari kerja yang valid bagi karyawan.
     *
     * @param Carbon $date Tanggal yang akan divalidasi.
     * @param Employee|null $employee Objek karyawan (opsional) untuk pengecekan cuti.
     *
     * @throws TimeValidationException Jika bukan hari kerja atau sedang cuti penuh.
     */
    public function validateWorkday(Carbon $date, ?Employee $employee = null): void
    {
        if (! $this->workdayService->isWorkday($date)) {
            throw TimeValidationException::notWorkday($date);
        }

        if ($employee) {
            $approvedLeave = $this->getApprovedLeave($employee, $date);
            if ($approvedLeave && ! $approvedLeave->is_half_day) {
                throw TimeValidationException::onFullLeave($approvedLeave);
            }
        }
    }

    /**
     * Mengambil data cuti yang telah disetujui untuk karyawan pada tanggal tertentu.
     *
     * @param Employee $employee Objek karyawan.
     * @param Carbon $date Objek tanggal.
     * @return Leave|null Data cuti jika ditemukan, atau null.
     */
    private function getApprovedLeave(Employee $employee, Carbon $date): ?Leave
    {
        return Leave::where('employee_id', $employee->id)
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->whereDate('date_start', '<=', $date->toDateString())
            ->whereDate('date_end', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Memvalidasi jendela waktu clock-in dan menentukan status kehadiran (tepat waktu, terlambat, atau absen).
     *
     * @param Employee $employee Objek karyawan.
     * @param Carbon $now Waktu saat ini.
     * @return array Status kehadiran dan detail keterlambatan.
     *
     * @throws AttendanceException Jika mencoba clock-in terlalu awal.
     */
    public function validateClockInWindow(Employee $employee, Carbon $now): array
    {
        $times = $this->getEmployeeScheduleTimes($employee, $now);
        $workStart = $times['work_start_time'];

        $maxEarlyMinutes = 120;
        if ($now->lt($workStart->copy()->subMinutes($maxEarlyMinutes))) {
            throw new AttendanceException('It is not time to clock in yet. Work starts at ' . $workStart->format('H:i'));
        }

        $lateTolerance = (int) ($times['late_tolerance_minutes'] ?? 10);
        $absentThreshold = (int) ($times['absent_threshold_minutes'] ?? 60);

        $actualDiff = $workStart->diffInMinutes($now, false);
        $actualDiff = max(0, $actualDiff);

        $lateMinutes = ($actualDiff > $lateTolerance) ? $actualDiff : 0;

        $isLate = $lateMinutes > 0;
        $isAbsent = $actualDiff >= $absentThreshold;

        return [
            'status' => $isAbsent ? 'absent' : ($isLate ? 'late' : 'on_time'),
            'late_minutes' => $lateMinutes,
            'is_late' => $isLate,
            'is_absent' => $isAbsent,
        ];
    }

    /**
     * Memvalidasi jendela waktu clock-out dan menghitung menit pulang awal atau lembur.
     *
     * @param Employee $employee Objek karyawan.
     * @param Carbon $now Waktu saat ini.
     * @return array Detail pulang awal, lembur, dan status persetujuan pulang awal.
     *
     * @throws AttendanceException Jika mencoba clock-out sebelum batas minimum (50% durasi kerja) tanpa izin.
     */
    public function validateClockOutWindow(Employee $employee, Carbon $now): array
    {
        $times = $this->getEmployeeScheduleTimes($employee, $now);

        $workStart = $times['work_start_time'];
        $workEnd = $times['work_end_time'];

        $totalWorkDuration = $workStart->diffInMinutes($workEnd);
        $earliestClockOut = (clone $workStart)->addMinutes(intval($totalWorkDuration / 2));

        // 1. Check for approved early leave request
        $isApproved = EarlyLeave::where('employee_id', $employee->id)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->whereHas('attendance', function ($q) use ($now) {
                $q->whereDate('date', $now->toDateString());
            })
            ->exists();

        // 2. If NOT approved, check the 50% work duration threshold
        if (! $isApproved && $now->lt($earliestClockOut)) {
            throw new AttendanceException(
                'It is not time to clock out yet. Minimum clock out time is '.$earliestClockOut->format('H:i'),
                ['reason' => 'too_early_for_clockout']
            );
        }

        // 3. Calculate early leave and overtime minutes
        $earlyLeaveMinutes = $now->lt($workEnd)
            ? $now->diffInMinutes($workEnd)
            : 0;

        $overtimeMinutes = $now->gt($workEnd)
            ? $workEnd->diffInMinutes($now)
            : 0;

        return [
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'is_early_leave' => $earlyLeaveMinutes > 0,
            'is_early_leave_approved' => $isApproved,
        ];
    }

    /**
     * Mengambil jam kerja karyawan dengan mekanisme 3-layer fallback.
     * 1. Shift override tanggal tertentu
     * 2. WorkSchedule default employee
     * 3. Default setting
     *
     * @param Employee $employee Objek karyawan.
     * @param Carbon $date Objek tanggal.
     */
    public function getEmployeeScheduleTimes(Employee $employee, Carbon $date): array
    {
        $dateStr = $date->toDateString();

        // -----------------------------
        // Layer 1: Shift override
        // -----------------------------
        $activeShift = $employee->shifts()
            ->where('shift_date', $dateStr)
            ->with('shiftTemplate')
            ->first();

        if ($activeShift) {
            $shift = $activeShift->shiftTemplate;

            return [
                'label' => $shift->name,
                'work_start_time' => Carbon::parse($dateStr.' '.$shift->start_time->format('H:i:s')),
                'work_end_time' => Carbon::parse($dateStr.' '.$shift->end_time->format('H:i:s')),
                'late_tolerance_minutes' => (int) ($shift->late_tolerance_minutes ?? 10),
                'requires_office_location' => (bool) ($shift->requires_office_location ?? true),
            ];
        }

        // -----------------------------
        // Layer 2: WorkSchedule default
        // -----------------------------
        $activeSchedule = $employee->activeWorkSchedule($dateStr)->first();
        if ($activeSchedule) {
            $ws = $activeSchedule->workSchedule;

            return [
                'label' => $ws->name,
                'work_start_time' => Carbon::parse($dateStr.' '.$ws->work_start_time),
                'work_end_time' => Carbon::parse($dateStr.' '.$ws->work_end_time),
                'requires_office_location' => (bool) ($ws->requires_office_location ?? true),
                'late_tolerance_minutes' => (int) ($ws->late_tolerance_minutes ?? $this->getDefaultLateTolerance()),
            ];
        }

        // -----------------------------
        // Layer 3: Default setting
        // -----------------------------
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];

        return [
            'label' => 'Standard Work Schedule',
            'work_start_time' => Carbon::parse($dateStr.' '.($setting['work_start_time'] ?? '09:00')),
            'work_end_time' => Carbon::parse($dateStr.' '.($setting['work_end_time'] ?? '17:00')),
            'requires_office_location' => true,
            'late_tolerance_minutes' => (int) ($setting['late_tolerance_minutes'] ?? 10),
        ];
    }

    /**
     * Mengambil toleransi keterlambatan default dari pengaturan sistem.
     *
     * @return int Toleransi keterlambatan dalam menit.
     */
    protected function getDefaultLateTolerance(): int
    {
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];

        return (int) ($setting['late_tolerance_minutes'] ?? 10);
    }
}
