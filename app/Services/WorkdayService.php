<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service class to determine if a specific date is a valid working day.
 */
class WorkdayService
{
    /**
     * Check if the given date is a workday (not a weekend or holiday).
     *
     * @param Carbon $date
     * @return bool
     */
    public function isWorkday(Carbon $date): bool
    {
        // 1. Normalize date to start of day and extract month-day format for recurring checks
        $date = $date->copy()->startOfDay();
        $currentMD = $date->format('m-d');

        // 2. Check if the date falls on a weekend
        if ($date->isWeekend()) {
            return false;
        }

        // 3. Check for specific (non-recurring) holidays within the date range
        $isSpecificHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isSpecificHoliday) {
            return false;
        }

        // 4. Check for yearly recurring holidays based on month and day
        $isYearlyHoliday = Holiday::where(function ($query) use ($currentMD) {
            $query->whereRaw("DATE_FORMAT(start_date, '%m-%d') = ?", [$currentMD])
                ->orWhereRaw("DATE_FORMAT(end_date, '%m-%d') = ?", [$currentMD]);
        })
            ->exists();

        // 5. Return true if it is not a yearly holiday
        return ! $isYearlyHoliday;
    }
}
