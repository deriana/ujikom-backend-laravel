<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\TimeValidationException;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\WorkdayService;
use Carbon\Carbon;

class TimeValidator
{
    protected WorkdayService $workdayService;

    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    public function validateWorkday(Carbon $date, ?Employee $employee = null): void
    {
        if ($employee) {
            $activeSchedule = $employee->activeWorkSchedule($date->toDateString())->first();
            if (! $activeSchedule) {
                throw TimeValidationException::notWorkday($date);
            }
        } else {
            if (! $this->workdayService->isWorkday($date)) {
                throw TimeValidationException::notWorkday($date);
            }
        }
    }

    public function validateClockInWindow(Employee $employee, Carbon $now): array
    {
        $times = $this->getEmployeeScheduleTimes($employee, $now);

        $workStart = $times['work_start_time'];
        $lateTolerance = (int) ($times['late_tolerance_minutes'] ?? 10);

        // Maksimal clock-in = start + toleransi menit
        $maxClockIn = (clone $workStart)->addMinutes($lateTolerance);

        if ($now->gt($maxClockIn)) {
            throw TimeValidationException::clockInLate($now, $maxClockIn);
        }

        $lateMinutes = max(0, $workStart->diffInMinutes($now, false));

        return [
            'is_late' => $lateMinutes > $lateTolerance,
            'late_minutes' => ($lateMinutes > $lateTolerance) ? $lateMinutes : 0,
        ];
    }

    public function validateClockOutWindow(Employee $employee, Carbon $now): array
    {
        $times = $this->getEmployeeScheduleTimes($employee, $now);

        $workStart = $times['work_start_time'];
        $workEnd   = $times['work_end_time'];

        // Minimal clock-out setengah hari kerja
        $totalMinutes = $workStart->diffInMinutes($workEnd);
        $minClockOut = (clone $workStart)->addMinutes($totalMinutes / 2);

        if ($now->lt($minClockOut)) {
            throw TimeValidationException::clockOutTooEarly($now, $minClockOut);
        }

        $earlyLeaveMinutes = $now->lt($workEnd) ? $now->diffInMinutes($workEnd) : 0;
        $overtimeMinutes   = $now->gt($workEnd) ? $workEnd->diffInMinutes($now) : 0;

        return [
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    public function getEmployeeScheduleTimes(Employee $employee, Carbon $date): array
    {
        $activeSchedule = $employee->activeWorkSchedule($date->toDateString())->first();

        if ($activeSchedule) {
            $ws = $activeSchedule->workSchedule;

            return [
                'work_start_time' => Carbon::parse($ws->work_start_time),
                'work_end_time'   => Carbon::parse($ws->work_end_time),
                'requires_office_location' => $ws->requires_office_location,
                'late_tolerance_minutes' => $ws->late_tolerance_minutes ?? $this->getDefaultLateTolerance(),
            ];
        }

        // fallback ke default setting dari database
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];

        return [
            'work_start_time' => Carbon::createFromFormat('H:i', $setting['work_start_time'] ?? '09:00'),
            'work_end_time'   => Carbon::createFromFormat('H:i', $setting['work_end_time'] ?? '17:00'),
            'requires_office_location' => $setting['requires_office_location'] ?? false,
            'late_tolerance_minutes' => $setting['late_tolerance_minutes'] ?? 10,
        ];
    }

    protected function getDefaultLateTolerance(): int
    {
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];
        return (int) ($setting['late_tolerance_minutes'] ?? 10);
    }
}
