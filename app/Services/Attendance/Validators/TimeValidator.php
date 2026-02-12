<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\TimeValidationException;
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

    public function validateWorkday(Carbon $date): void
    {
        if (! $this->workdayService->isWorkday($date)) {
            throw TimeValidationException::notWorkday($date);
        }
    }

    public function validateClockInWindow(Carbon $now): array
    {
        $settings = $this->getAttendanceSetting();
        $workStart = Carbon::createFromFormat('H:i', $settings['work_start_time']);
        $maxClockIn = (clone $workStart)->addHours(2);

        if ($now->gt($maxClockIn)) {
            throw TimeValidationException::clockInLate($now, $maxClockIn);
        }

        $lateTolerance = (int) $settings['late_tolerance_minutes'];
        $lateMinutes = max(0, $workStart->diffInMinutes($now, false));

        return [
            'is_late' => $lateMinutes > $lateTolerance,
            'late_minutes' => ($lateMinutes > $lateTolerance) ? $lateMinutes : 0,
        ];
    }

    public function validateClockOutWindow(Carbon $now): array
    {
        $settings = $this->getAttendanceSetting();
        $workStart = Carbon::createFromFormat('H:i', $settings['work_start_time']);
        $workEnd = Carbon::createFromFormat('H:i', $settings['work_end_time']);

        // Min clock out is half-day? (Based on old logic: halfway between start and end)
        $totalHours = $workStart->diffInHours($workEnd);
        $minClockOut = (clone $workStart)->addHours($totalHours / 2);

        if ($now->lt($minClockOut)) {
            throw TimeValidationException::clockOutTooEarly($now, $minClockOut);
        }

        $earlyLeaveMinutes = $now->lt($workEnd) ? $now->diffInMinutes($workEnd) : 0;
        $overtimeMinutes = $now->gt($workEnd) ? $workEnd->diffInMinutes($now) : 0;

        return [
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    protected function getAttendanceSetting(): array
    {
        $setting = Setting::where('key', 'attendance')->first();

        return $setting?->values ?? [
            'late_tolerance_minutes' => 10,
            'work_start_time' => '09:00',
            'work_end_time' => '17:00',
        ];

    }
}
