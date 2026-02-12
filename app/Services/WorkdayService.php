<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkdayService
{
    public function isWorkday(Carbon $date): bool
    {
        $date = $date->copy();

        // Weekend
        if ($date->isWeekend()) {
            return false;
        }

        // Libur spesifik tanggal
        if (Holiday::whereDate('date', $date)->exists()) {
            return false;
        }

        // Libur tahunan berulang (misal 17 Agustus tiap tahun)
        if (Holiday::where('is_recurring', true)
            ->whereMonth('date', $date->month)
            ->whereDay('date', $date->day)
            ->exists()) {
            return false;
        }

        return true;
    }
}
