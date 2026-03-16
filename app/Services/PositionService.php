<?php

namespace App\Services;

use App\Models\Allowance;
use App\Models\Position;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class PositionService
 *
 * Menangani logika bisnis untuk manajemen jabatan (position),
 * termasuk pengaturan gaji pokok dan sinkronisasi tunjangan terkait.
 */
class PositionService
{
    /**
     * Mengambil semua data jabatan beserta pembuat dan tunjangan terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve positions with eager loaded relationships
        return Position::with(['creator', 'allowances'])->latest()->get();
    }

    /**
     * Menyimpan data jabatan baru dan menyinkronkan tunjangannya.
     *
     * @param array $data Data jabatan (name, base_salary, allowances).
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Position
     */
    public function store(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Create the position record
            $position = Position::create([
                'name' => $data['name'],
                'base_salary' => $data['base_salary'],
            ]);

            // 2. Sync associated allowances if provided
            $this->syncAllowances($position, $data);

            return $position->load('allowances');
        });
    }

    /**
     * Memperbarui data jabatan yang sudah ada beserta tunjangannya.
     *
     * @param Position $position Objek jabatan yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Position
     * @throws Exception Jika jabatan sudah dihapus atau merupakan cadangan sistem.
     */
    public function update(Position $position, array $data, int $userId)
    {
        // 1. Validate if the position can be updated
        if ($position->trashed()) {
            throw new Exception('Cannot update a deleted position');
        }
        if ($position->system_reserve) {
            throw new Exception('Cannot update a system reserve position');
        }

        return DB::transaction(function () use ($position, $data) {
            // 2. Update position basic information
            $position->update([
                'name'   => $data['name']   ?? $position->name,
                'base_salary' => $data['base_salary'] ?? $position->amount,
            ]);

            $this->syncAllowances($position, $data);

            return $position->load('allowances');
        });
    }

    /**
     * Menghapus jabatan secara lunak (soft delete).
     *
     * @param Position $position Objek jabatan yang akan dihapus.
     * @return bool
     * @throws Exception Jika jabatan sudah dalam status terhapus atau merupakan cadangan sistem.
     */
    public function delete(Position $position): bool
    {
        // 1. Security and state validation
        if ($position->trashed()) {
            throw new Exception('Cannot delete a deleted position');
        }
        if ($position->system_reserve) {
            throw new Exception('Cannot delete a system reserve position');
        }

        // 2. Perform soft delete
        return DB::transaction(fn () => $position->delete());
    }

    /**
     * Memulihkan data jabatan yang telah dihapus lunak.
     *
     * @param string $uuid UUID jabatan.
     * @return Position
     * @throws Exception Jika jabatan tidak dalam status terhapus.
     */
    public function restore(string $uuid): Position
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the trashed position
            $position = Position::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $position->trashed()) {
                throw new Exception('Position is not deleted');
            }

            // 2. Restore the position
            $position->restore();

            return $position;
        });
    }

    /**
     * Menghapus data jabatan secara permanen dari database.
     *
     * @param string $uuid UUID jabatan.
     * @return bool
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the position including trashed ones
            $position = Position::withTrashed()->whereUuid($uuid)->firstOrFail();

            // 2. Force delete the position
            $position->forceDelete();

            return true;
        });
    }

    /**
     * Mengambil semua daftar jabatan yang telah dihapus lunak.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        return Position::onlyTrashed()->latest()->get();
    }

    /**
     * Menyinkronkan tunjangan untuk sebuah jabatan (Pemetaan UUID ke ID).
     *
     * @param Position $position Objek jabatan.
     * @param array $data Data tunjangan yang berisi array UUID dan nominal.
     * @return void
     * @throws Exception Jika UUID tunjangan tidak ditemukan.
     */
    private function syncAllowances(Position $position, array $data): void
    {
        // 1. Skip if allowances key is not present
        if (! array_key_exists('allowances', $data)) {
            return;
        }
        // 2. Detach all if array is empty
        if (empty($data['allowances'])) {
            $position->allowances()->detach();
            return;
        }

        $allowances = Allowance::whereIn(
            'uuid',
            collect($data['allowances'])->pluck('uuid')
        )->get()->keyBy('uuid');

        $syncData = [];

        foreach ($data['allowances'] as $item) {
            // 3. Security check for invalid UUIDs
            if (! isset($allowances[$item['uuid']])) {
                throw new Exception("Allowance UUID {$item['uuid']} not found");
            }

            // 4. Map UUID to ID and include pivot data
            $syncData[$allowances[$item['uuid']]->id] = [
                'amount' => $item['amount'] ?? null,
            ];
        }

        // 5. Sync the pivot table
        $position->allowances()->sync($syncData);
    }
}
