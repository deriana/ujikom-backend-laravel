<?php

namespace App\Services;

use App\Models\PointRule;
use Illuminate\Support\Facades\DB;
use DomainException;
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
                'category' => $data['category'],
                'event_name' => $data['event_name'],
                'points' => $data['points'],
                'operator' => $data['operator'] ?? '==',
                'min_value' => $data['min_value'] ?? null,
                'max_value' => $data['max_value'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : true,
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
            // Jika aturan diproteksi sistem, cegah perubahan pada logika inti
            if ($pointRule->system_reserve) {
                $restrictedFields = ['operator', 'min_value', 'max_value', 'category', 'is_active'];
                $attemptingToChangeRestricted = false;
                foreach ($restrictedFields as $field) {
                    if (array_key_exists($field, $data)) $attemptingToChangeRestricted = true;
                }

                if ($attemptingToChangeRestricted) {
                    throw new \DomainException("Cannot modify core logic (operator, values, or category) of a system reserved rule.");
                }
            }

            $pointRule->update([
                'category' => $data['category'] ?? $pointRule->category,
                'event_name' => $data['event_name'] ?? $pointRule->event_name,
                'points' => $data['points'] ?? $pointRule->points,
                'operator' => $data['operator'] ?? $pointRule->operator,
                'min_value' => array_key_exists('min_value', $data) ? $data['min_value'] : $pointRule->min_value,
                'max_value' => array_key_exists('max_value', $data) ? $data['max_value'] : $pointRule->max_value,
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
        return DB::transaction(function () use ($pointRule) {
            if ($pointRule->system_reserve) {
                throw new \DomainException("System reserved rules cannot be deleted.");
            }
            return (bool) $pointRule->delete();
        });
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
