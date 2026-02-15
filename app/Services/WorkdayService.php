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

        // 1. Cek Weekend
        if ($date->isWeekend()) {
            return false;
        }

        // 2. Cek Libur Spesifik (Tahun, Bulan, Tanggal harus sama persis)
        $isSpecificHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isSpecificHoliday) {
            return false;
        }

        // 3. Cek Libur Tahunan (Hanya Bulan dan Tanggal yang sama)
        // Walaupun is_recurring false, kalau tanggal & bulan pas, kita anggap libur
        $isYearlyHoliday = Holiday::where(function ($query) use ($currentMD) {
            $query->whereRaw("DATE_FORMAT(start_date, '%m-%d') = ?", [$currentMD])
                ->orWhereRaw("DATE_FORMAT(end_date, '%m-%d') = ?", [$currentMD]);
        })
            ->exists();

        return ! $isYearlyHoliday;
    }
}
