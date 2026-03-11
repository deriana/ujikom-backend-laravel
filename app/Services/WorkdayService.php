<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
     * @param Carbon $date Objek tanggal yang akan diperiksa.
     * @return bool True jika hari kerja, false jika hari libur atau akhir pekan.
     */
    public function isWorkday(Carbon $date): bool
    {
        // 1. Normalisasi tanggal ke awal hari dan ambil format bulan-tanggal untuk pengecekan berulang
        $date = $date->copy()->startOfDay();
        $currentMD = $date->format('m-d');

        // 2. Periksa apakah tanggal jatuh pada akhir pekan
        if ($date->isWeekend()) {
            return false;
        }

        // 3. Periksa hari libur spesifik (tidak berulang) dalam rentang tanggal
        $isSpecificHoliday = Holiday::where('is_recurring', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isSpecificHoliday) {
            return false;
        }

        // 4. Periksa hari libur tahunan yang berulang berdasarkan bulan dan hari
        $isYearlyHoliday = Holiday::where(function ($query) use ($currentMD) {
            $query->whereRaw("DATE_FORMAT(start_date, '%m-%d') = ?", [$currentMD])
                ->orWhereRaw("DATE_FORMAT(end_date, '%m-%d') = ?", [$currentMD]);
        })
            ->exists();

        // 5. Kembalikan true jika bukan merupakan hari libur tahunan
        return ! $isYearlyHoliday;
    }
}
