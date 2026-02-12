<?php

namespace App\Exceptions\Attendance;

use Carbon\Carbon;

class TimeValidationException extends AttendanceException
{
    public static function notWorkday(Carbon $date): self
    {
        return new self('Hari ini bukan hari kerja.', [
            'reason' => 'non_working_day',
            'date' => $date->toDateString()
        ]);
    }

    public static function clockInLate(Carbon $now, Carbon $maxTime): self
    {
        return new self('Melewati batas waktu absen masuk.', [
            'reason' => 'clock_in_over_limit',
            'time' => $now->toTimeString(),
            'limit' => $maxTime->toTimeString()
        ]);
    }

    public static function clockOutTooEarly(Carbon $now, Carbon $minTime): self
    {
        return new self('Belum boleh absen pulang.', [
            'reason' => 'clock_out_too_early',
            'time' => $now->toTimeString(),
            'limit' => $minTime->toTimeString()
        ]);
    }
}
