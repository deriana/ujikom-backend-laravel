<?php

namespace App\Services;

use App\Models\PointRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PointRuleService
 *
 * Menangani logika bisnis untuk manajemen aturan poin (point rules),
 * termasuk penentuan poin untuk berbagai aktivitas karyawan.
 */
class PointRuleService
{
    /**
     * Mengambil semua daftar aturan poin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return PointRule::latest()->get();
    }

    /**
     * Menyimpan aturan poin baru ke dalam database.
     *
     * @param array $data Data aturan (event_name, points, description, is_active).
     * @return PointRule
     */
    public function store(array $data): PointRule
    {
        return DB::transaction(function () use ($data) {
            return PointRule::create([
                'uuid' => (string) Str::uuid(),
                'event_name' => $data['event_name'],
                'points' => $data['points'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Memperbarui data aturan poin yang sudah ada.
     *
     * @param PointRule $pointRule Objek aturan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return PointRule
     */
    public function update(PointRule $pointRule, array $data): PointRule
    {
        return DB::transaction(function () use ($pointRule, $data) {
            $pointRule->update([
                'event_name' => $data['event_name'] ?? $pointRule->event_name,
                'points' => $data['points'] ?? $pointRule->points,
                'description' => $data['description'] ?? $pointRule->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $pointRule->is_active,
            ]);

            return $pointRule;
        });
    }

    /**
     * Menghapus data aturan poin.
     *
     * @param PointRule $pointRule Objek aturan yang akan dihapus.
     * @return bool
     */
    public function delete(PointRule $pointRule): bool
    {
        return DB::transaction(fn () => (bool) $pointRule->delete());
    }

    /**
     * Mengubah status aktif/non-aktif aturan poin secara cepat.
     *
     * @param PointRule $pointRule
     * @return PointRule
     */
    public function toggleStatus(PointRule $pointRule): PointRule
    {
        return DB::transaction(function () use ($pointRule) {
            $pointRule->update([
                'is_active' => ! $pointRule->is_active
            ]);
            return $pointRule;
        });
    }
}
