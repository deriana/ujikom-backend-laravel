<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Class SettingService
 *
 * Menangani logika bisnis untuk pengelolaan pengaturan sistem (settings),
 * termasuk pengambilan data tunggal, banyak data, dan pembaruan konfigurasi.
 */
class SettingService
{
    /**
     * Mengambil pengaturan spesifik berdasarkan kuncinya atau membuat default jika tidak ada.
     *
     * @param string $key Kunci pengaturan.
     * @return Setting Objek model Setting.
     */
    public function get(string $key): Setting
    {
        // 1. Ambil catatan pengaturan berdasarkan kunci atau inisialisasi baru dengan nilai kosong
        return Setting::firstOrCreate(['key' => $key], ['values' => []]);
    }

    /**
     * Mengambil banyak pengaturan berdasarkan array kunci.
     *
     * @param array $keys Daftar kunci pengaturan.
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengaturan yang diindeks berdasarkan kunci.
     */
    public function getMany(array $keys)
    {
        // 1. Ambil pengaturan yang cocok dengan kunci yang diberikan dan indeks berdasarkan kunci untuk akses mudah
        return Setting::whereIn('key', $keys)->get()->keyBy('key');
    }

    /**
     * Memperbarui atau membuat catatan pengaturan dengan nilai yang diberikan.
     *
     * @param string $key Kunci pengaturan.
     * @param array $values Data nilai pengaturan dalam bentuk array.
     * @return Setting Objek pengaturan yang diperbarui atau dibuat.
     */
    public function update(string $key, array $values): Setting
    {
        return DB::transaction(function () use ($key, $values) {
            // 1. Lakukan operasi update atau create di dalam transaksi database
            return Setting::updateOrCreate(
                ['key' => $key],
                ['values' => $values]
            );
        });
    }
}
