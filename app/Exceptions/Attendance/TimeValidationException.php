<?php

namespace App\Exceptions\Attendance;

use App\Models\Leave;
use Carbon\Carbon;

class TimeValidationException extends AttendanceException
{
    public static function notWorkday(Carbon $date): self
    {
        return new self('Today is not a working day.', [
            'reason' => 'non_working_day',
            'date' => $date->toDateString()
        ]);
    }

    public static function clockInLate(Carbon $now, Carbon $maxTime): self
    {
        return new self('Clock-in time limit exceeded.', [
            'reason' => 'clock_in_over_limit',
            'time' => $now->toTimeString(),
            'limit' => $maxTime->toTimeString()
        ]);
    }

    public static function clockOutTooEarly(Carbon $now, Carbon $minTime): self
    {
        return new self('Not allowed to clock out yet.', [
            'reason' => 'clock_out_too_early',
            'time' => $now->toTimeString(),
            'limit' => $minTime->toTimeString()
        ]);
    }

    public static function onFullLeave(Leave $leave): self
    {
        return new self('You cannot record attendance. You have an approved Full Leave for today.', [
            'reason' => 'on_full_leave',
            'leave_type' => $leave->leaveType?->name,
            'date_start' => $leave->date_start->toDateString(),
            'date_end' => $leave->date_end->toDateString(),
            'is_half_day' => $leave->is_half_day ? 'half_day' : 'full_day'
        ]);
    }
}
