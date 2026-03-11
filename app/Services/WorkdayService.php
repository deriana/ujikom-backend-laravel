<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

/**
 * Class WorkdayService
 *
 * Layanan untuk menentukan apakah tanggal tertentu merupakan hari kerja yang valid.
 */
class WorkdayService
{
    /**
     * Memeriksa apakah tanggal yang diberikan adalah hari kerja (bukan akhir pekan atau hari libur).
     *
     * @param  Carbon  $date  Objek tanggal yang akan diperiksa.
     * @return bool True jika hari kerja, false jika hari libur atau akhir pekan.
     */
    public function isWorkday(Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();

        // 1. Periksa apakah tanggal jatuh pada akhir pekan
        if ($date->isWeekend()) {
            return false;
        }

        // 2. Periksa hari libur spesifik (tidak berulang)
        $isSpecificHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isSpecificHoliday) {
            return false;
        }

        // 3. Periksa hari libur tahunan yang berulang (Database Agnostic)
        // Kita gunakan whereMonth dan whereDay agar Laravel yang menyesuaikan SQL-nya
        $isYearlyHoliday = Holiday::where('is_recurring', true)
            ->where(function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->whereMonth('start_date', $date->month)
                        ->whereDay('start_date', $date->day);
                })->orWhere(function ($q) use ($date) {
                    $q->whereMonth('end_date', $date->month)
                        ->whereDay('end_date', $date->day);
                });
            })
            ->exists();

        return ! $isYearlyHoliday;
    }
}
