<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkdayService
{
    public function isWorkday(Carbon $date): bool
    {
        $date = $date->copy();

        // 1. Weekend
        if ($date->isWeekend()) {
            return false;
        }

        // 2. Libur tidak berulang (range tanggal)
        $isHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isHoliday) {
            return false;
        }

        // 3. Libur berulang tiap tahun (cek bulan & hari dalam range)
        $isRecurringHoliday = Holiday::where('is_recurring', true)
            ->get()
            ->contains(function ($holiday) use ($date) {
                $start = Carbon::parse($holiday->start_date)->year($date->year);
                $end   = Carbon::parse($holiday->end_date)->year($date->year);

                return $date->between($start, $end);
            });

        if ($isRecurringHoliday) {
            return false;
        }

        return true;
    }
}
