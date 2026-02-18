<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkdayService
{
    public function isWorkday(Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();
        $currentMD = $date->format('m-d');

        if ($date->isWeekend()) {
            return false;
        }

        $isSpecificHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isSpecificHoliday) {
            return false;
        }

        $isYearlyHoliday = Holiday::where(function ($query) use ($currentMD) {
            $query->whereRaw("DATE_FORMAT(start_date, '%m-%d') = ?", [$currentMD])
                ->orWhereRaw("DATE_FORMAT(end_date, '%m-%d') = ?", [$currentMD]);
        })
            ->exists();

        return ! $isYearlyHoliday;
    }
}
