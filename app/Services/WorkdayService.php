<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkdayService
{
    public function isWorkday(Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();

        if ($date->isWeekend()) {
            return false;
        }

        // 2. Libur Single/Range (is_recurring = false)
        $isHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isHoliday) {
            return false;
        }

        // 3. Libur Berulang (is_recurring = true)
        // Kita cek berdasarkan bulan dan hari saja
        $isRecurringHoliday = Holiday::where('is_recurring', true)
            ->whereRaw("DATE_FORMAT(start_date, '%m-%d') <= ?", [$date->format('m-d')])
            ->whereRaw("DATE_FORMAT(end_date, '%m-%d') >= ?", [$date->format('m-d')])
            ->exists();

        return ! $isRecurringHoliday;
    }
}
