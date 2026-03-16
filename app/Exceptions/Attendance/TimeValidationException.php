<?php

namespace App\Exceptions\Attendance;

use App\Models\Leave;
use Carbon\Carbon;

/**
 * Class TimeValidationException
 *
 * Exception khusus untuk menangani kesalahan validasi waktu pada proses absensi.
 */
class TimeValidationException extends AttendanceException
{
    /**
     * Exception ketika hari ini bukan merupakan hari kerja.
     *
     * @param Carbon $date Tanggal yang diperiksa
     * @return self
     */
    public static function notWorkday(Carbon $date): self
    {
        return new self('Today is not a working day.', [
            'reason' => 'non_working_day',
            'date' => $date->toDateString()
        ]);
    }

    /**
     * Exception ketika waktu melakukan clock-in sudah melewati batas yang diizinkan.
     *
     * @param Carbon $now Waktu saat ini
     * @param Carbon $maxTime Batas waktu maksimal clock-in
     * @return self
     */
    public static function clockInLate(Carbon $now, Carbon $maxTime): self
    {
        return new self('Clock-in time limit exceeded.', [
            'reason' => 'clock_in_over_limit',
            'time' => $now->toTimeString(),
            'limit' => $maxTime->toTimeString()
        ]);
    }

    /**
     * Exception ketika mencoba melakukan clock-out sebelum waktu yang ditentukan.
     *
     * @param Carbon $now Waktu saat ini
     * @param Carbon $minTime Batas waktu minimal clock-out
     * @return self
     */
    public static function clockOutTooEarly(Carbon $now, Carbon $minTime): self
    {
        return new self('Not allowed to clock out yet.', [
            'reason' => 'clock_out_too_early',
            'time' => $now->toTimeString(),
            'limit' => $minTime->toTimeString()
        ]);
    }

    /**
     * Exception ketika karyawan sedang dalam masa cuti penuh (Full Leave).
     *
     * @param Leave $leave Objek data cuti
     * @return self
     */
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
