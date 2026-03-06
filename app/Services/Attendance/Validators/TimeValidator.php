<?php

namespace App\Services\Attendance\Validators;

use App\Enums\ApprovalStatus;
use App\Exceptions\Attendance\AttendanceException;
use App\Exceptions\Attendance\TimeValidationException;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\WorkdayService;
use Carbon\Carbon;

class TimeValidator
{
    /**
     * The workday service instance.
     */
    protected WorkdayService $workdayService;

    /**
     * Create a new validator instance.
     */
    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    /**
     * Validate if the given date is a valid workday for the employee.
     *
     * @param Carbon $date
     * @param Employee|null $employee
     * @throws TimeValidationException
     */
    public function validateWorkday(Carbon $date, ?Employee $employee = null): void
    {
        if ($employee) {
            $activeSchedule = $employee->activeWorkSchedule($date->toDateString())->first();
            $activeShift = $employee->employeeShifts()->where('shift_date', $date->toDateString())->first();

            if (! $activeShift && ! $activeSchedule) {
                throw TimeValidationException::notWorkday($date);
            }
        } else {
            if (! $this->workdayService->isWorkday($date)) {
                throw TimeValidationException::notWorkday($date);
            }
        }
    }

    /**
     * Validate the clock-in window and determine attendance status.
     *
     * @param Employee $employee
     * @param Carbon $now
     * @return array
     * @throws AttendanceException
     */
    public function validateClockInWindow(Employee $employee, Carbon $now): array
    {
        $times = $this->getEmployeeScheduleTimes($employee, $now);
        $workStart = $times['work_start_time'];

        // 1. Check if it's too early (e.g., cannot clock in more than 2 hours before shift)
        $maxEarlyMinutes = 120; // 2 hours
        if ($now->lt($workStart->copy()->subMinutes($maxEarlyMinutes))) {
            throw new AttendanceException(
                'It is not time to clock in yet. Your shift starts at '.$workStart->format('H:i'),
                ['reason' => 'too_early_for_clockin']
            );
        }

        // 2. Tolerance Configuration
        $lateTolerance = (int) ($times['late_tolerance_minutes'] ?? 10);
        $absentThreshold = (int) ($times['absent_threshold_minutes'] ?? 60);

        // 3. Calculate difference
        $diffMinutes = $workStart->diffInMinutes($now, false);
        $lateMinutes = max(0, $diffMinutes);

        $isLate = $lateMinutes > $lateTolerance;
        $isAbsent = $lateMinutes >= $absentThreshold;

        return [
            'status' => $isAbsent ? 'absent' : ($isLate ? 'late' : 'on_time'),
            'late_minutes' => $lateMinutes,
            'is_late' => $isLate,
            'is_absent' => $isAbsent,
        ];
    }

    /**
     * Validate the clock-out window and calculate early leave or overtime.
     *
     * @param Employee $employee
     * @param Carbon $now
     * @return array
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
     * Ambil jam kerja employee dengan 3-layer fallback:
     * 1. Shift override tanggal tertentu
     * 2. WorkSchedule default employee
     * 3. Default setting
     */
    public function getEmployeeScheduleTimes(Employee $employee, Carbon $date): array
    {
        // -----------------------------
        // Layer 1: Shift override
        // -----------------------------
        $activeShift = $employee->shifts()
            ->where('shift_date', $date->toDateString())
            ->with('shiftTemplate')
            ->first();

        if ($activeShift) {
            $shift = $activeShift->shiftTemplate;

            return [
                'work_start_time' => Carbon::parse($shift->start_time),
                'work_end_time' => Carbon::parse($shift->end_time),
                'late_tolerance_minutes' => $shift->late_tolerance_minutes ?? 10,
                'requires_office_location' => $shift->requires_office_location ?? false,
            ];
        }

        // -----------------------------
        // Layer 2: WorkSchedule default
        // -----------------------------
        $activeSchedule = $employee->activeWorkSchedule($date->toDateString())->first();
        if ($activeSchedule) {
            $ws = $activeSchedule->workSchedule;

            return [
                'work_start_time' => Carbon::parse($ws->work_start_time),
                'work_end_time' => Carbon::parse($ws->work_end_time),
                'requires_office_location' => $ws->requires_office_location,
                'late_tolerance_minutes' => $ws->late_tolerance_minutes ?? $this->getDefaultLateTolerance(),
            ];
        }

        // -----------------------------
        // Layer 3: Default setting
        // -----------------------------
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];

        return [
            'work_start_time' => Carbon::createFromFormat('H:i', $setting['work_start_time'] ?? '09:00'),
            'work_end_time' => Carbon::createFromFormat('H:i', $setting['work_end_time'] ?? '17:00'),
            'requires_office_location' => $setting['requires_office_location'] ?? false,
            'late_tolerance_minutes' => $setting['late_tolerance_minutes'] ?? 10,
        ];
    }

    /**
     * Get the default late tolerance from system settings.
     */
    protected function getDefaultLateTolerance(): int
    {
        $setting = Setting::where('key', 'attendance')->first()?->values ?? [];

        return (int) ($setting['late_tolerance_minutes'] ?? 10);
    }
}
