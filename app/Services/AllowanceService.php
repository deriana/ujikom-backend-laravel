<?php

namespace App\Services;

use App\Models\Allowance;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Class AllowanceService
 *
 * Menangani logika bisnis untuk manajemen tunjangan (allowance),
 * termasuk operasi CRUD, soft delete, dan pemulihan data.
 */
class AllowanceService
{
    /**
     * Mengambil semua data tunjangan beserta pembuat dan jabatan terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return Allowance::with(['creator', 'positions'])
            ->latest()
            ->get();
    }

    /**
     * Menyimpan data tunjangan baru ke dalam database.
     *
     * @param array $data Data tunjangan (name, amount, type).
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Allowance
     */
    public function store(array $data, int $userId): Allowance
    {
        return DB::transaction(function () use ($data, $userId) {
            return Allowance::create([
                'name' => $data['name'],
                'amount' => $data['amount'],
                'type' => $data['type'],
            ]);
        });
    }

    /**
     * Memperbarui data tunjangan yang sudah ada.
     *
     * @param Allowance $allowance Objek tunjangan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Allowance
     * @throws Exception Jika tunjangan sudah dihapus (soft-deleted).
     */
    public function update(Allowance $allowance, array $data, int $userId): Allowance
    {
        if ($allowance->trashed()) {
            throw new Exception('Cannot update a deleted allowance');
        }

        return DB::transaction(function () use ($allowance, $data) {
            $allowance->update([
                'name'   => $data['name']   ?? $allowance->name,
                'amount' => $data['amount'] ?? $allowance->amount,
                'type'   => $data['type']   ?? $allowance->type,
            ]);

            return $allowance;
        });
    }

    /**
     * Menghapus tunjangan secara lunak (soft delete).
     *
     * @param Allowance $allowance Objek tunjangan yang akan dihapus.
     * @return bool
     * @throws Exception Jika tunjangan sudah dalam status terhapus.
     */
    public function delete(Allowance $allowance): bool
    {
        if ($allowance->trashed()) {
            throw new Exception('Cannot delete a deleted allowance');
        }

        return DB::transaction(fn () => $allowance->delete());
    }

    /**
     * Memulihkan data tunjangan yang telah dihapus lunak berdasarkan UUID.
     *
     * @param string $uuid UUID tunjangan.
     * @return Allowance
     * @throws Exception Jika tunjangan tidak dalam status terhapus.
     */
    public function restore(string $uuid): Allowance
    {
        return DB::transaction(function () use ($uuid) {
            $allowance = Allowance::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $allowance->trashed()) {
                throw new Exception('Allowance is not deleted');
            }

            $allowance->restore();

            return $allowance;
        });
    }

    /**
     * Menghapus data tunjangan secara permanen dari database.
     *
     * @param string $uuid UUID tunjangan.
     * @return bool
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $allowance = Allowance::withTrashed()->whereUuid($uuid)->firstOrFail();
            return (bool) $allowance->forceDelete();
        });
    }

    /**
     * Mengambil semua daftar tunjangan yang telah dihapus lunak.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        return Allowance::onlyTrashed()->latest()->get();
    }
}
