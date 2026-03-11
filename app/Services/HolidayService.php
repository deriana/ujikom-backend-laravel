<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

/**
 * Class HolidayService
 *
 * Menangani logika bisnis untuk manajemen hari libur (holiday),
 * termasuk operasi CRUD dan pengaturan hari libur berulang.
 */
class HolidayService
{
    /**
     * Mengambil semua data hari libur beserta informasi pembuatnya.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve holidays with eager loaded creator relationship
        return Holiday::with(['creator'])
            ->latest()
            ->get();
    }

    /**
     * Menyimpan data hari libur baru ke dalam database.
     *
     * @param array $data Data hari libur (name, start_date, end_date, is_recurring).
     * @param int $userId ID pengguna yang membuat data.
     * @return Holiday Objek hari libur yang berhasil dibuat.
     */
    public function store(array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Prepare date variables
            $startDate = $data['start_date'];
            $endDate   = $data['end_date'] ?? null;

            // 2. Create the holiday record in the database
            return Holiday::create([
                'name'          => $data['name'],
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'is_recurring'  => $data['is_recurring'] ?? false,
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ]);
        });
    }

    /**
     * Memperbarui data hari libur yang sudah ada.
     *
     * @param Holiday $holiday Objek hari libur yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param int $userId ID pengguna yang melakukan pembaruan.
     * @return Holiday Objek hari libur setelah diperbarui.
     */
    public function update(Holiday $holiday, array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($holiday, $data, $userId) {
            // 1. Update the holiday attributes with provided data or keep existing values
            $holiday->update([
                'name'          => $data['name'] ?? $holiday->name,
                'start_date'    => $data['start_date'] ?? $holiday->start_date,
                'end_date'      => array_key_exists('end_date', $data)
                                    ? $data['end_date']
                                    : $holiday->end_date,
                'is_recurring'  => $data['is_recurring'] ?? $holiday->is_recurring,
                'updated_by_id' => $userId,
            ]);

            return $holiday;
        });
    }

    /**
     * Menghapus data hari libur dari database.
     *
     * @param Holiday $holiday Objek hari libur yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(Holiday $holiday): bool
    {
        return DB::transaction(function () use ($holiday) {
            // 1. Perform the deletion of the holiday record
            return (bool) $holiday->delete();
        });
    }
}
