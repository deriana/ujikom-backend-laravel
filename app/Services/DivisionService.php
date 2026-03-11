<?php

namespace App\Services;

use App\Models\Division;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class DivisionService
 *
 * Menangani logika bisnis untuk manajemen divisi dan tim,
 * termasuk operasi CRUD, sinkronisasi tim, dan pemulihan data.
 */
class DivisionService
{
    /**
     * Mengambil semua divisi beserta pembuat dan tim terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return Division::with(['creator', 'teams'])->latest()->get();
    }

    /**
     * Mengambil semua divisi beserta tim dan karyawan/pengguna yang ditugaskan.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDivisionsWithTeamsAndEmployees()
    {
        return Division::with([
            'teams.employees.user' => function ($query) {
                $query->where('is_active', true);
            },
            'teams.employees.position',
            'teams.employees.media'
        ])
        ->latest()
        ->get();

    }

    /**
     * Menyimpan divisi baru dan menyinkronkan tim di dalamnya.
     *
     * @param array $data Data divisi (name, code, teams).
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Division
     */
    public function store(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $division = Division::create([
                'name' => $data['name'],
                'code' => $data['code'],
            ]);

            $this->syncTeams($division, $data['teams'] ?? [], $userId);

            return $division->load('teams');
        });
    }

    /**
     * Memperbarui divisi yang sudah ada beserta timnya.
     *
     * @param Division $division Objek divisi yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return Division
     * @throws Exception Jika divisi adalah cadangan sistem atau sudah dihapus.
     */
    public function update(Division $division, array $data, int $userId)
    {
        if ($division->system_reserve) {
            throw new Exception('Cannot update a system reserve division');
        }
        if ($division->trashed()) {
            throw new Exception('Cannot update a deleted division');
        }

        return DB::transaction(function () use ($division, $data, $userId) {
            $division->update([
                'name' => $data['name'] ?? $division->name,
                'code' => $data['code'] ?? $division->code,
            ]);

            if (array_key_exists('teams', $data)) {
                $this->syncTeams($division, $data['teams'] ?? [], $userId);
            }

            return $division->load('teams');
        });
    }

    /**
     * Menghapus divisi secara lunak (soft delete) beserta tim terkaitnya.
     *
     * @param Division $division Objek divisi yang akan dihapus.
     * @return bool
     * @throws Exception Jika divisi adalah cadangan sistem atau sudah dalam status terhapus.
     */
    public function delete(Division $division): bool
    {
        if ($division->system_reserve) {
            throw new Exception('Cannot delete a system reserve division');
        }
        if ($division->trashed()) {
            throw new Exception('Cannot delete a deleted division');
        }

        return DB::transaction(function () use ($division) {
            $division->teams()->delete();
            $division->delete();

            return true;
        });
    }

    /**
     * Memulihkan divisi yang telah dihapus lunak beserta timnya.
     *
     * @param string $uuid UUID divisi.
     * @return Division
     * @throws Exception Jika divisi tidak dalam status terhapus.
     */
    public function restore(string $uuid): Division
    {
        return DB::transaction(function () use ($uuid) {
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $division->trashed()) {
                throw new Exception('Division is not deleted');
            }

            $division->restore();
            $division->teams()->onlyTrashed()->restore();

            return $division->load('teams');
        });
    }

    /**
     * Menghapus divisi dan semua timnya secara permanen dari database.
     *
     * @param string $uuid UUID divisi.
     * @return bool
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            $division->teams()->withTrashed()->forceDelete();
            $division->forceDelete();

            return true;
        });
    }

    /**
     * Mengambil semua daftar divisi yang telah dihapus lunak.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        return Division::onlyTrashed()->With('teams')->latest()->get();
    }

    /**
     * Menyinkronkan tim untuk sebuah divisi (Buat, Perbarui, atau Hapus).
     *
     * @param Division $division Objek divisi.
     * @param array $teams Array data tim.
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return void
     */
    private function syncTeams(Division $division, array $teams, int $userId): void
    {
        if (empty($teams)) {
            $division->teams()->delete();

            return;
        }

        $existingTeams = $division->teams()->get()->keyBy('uuid');
        $syncUUIDs = [];

        foreach ($teams as $teamData) {
            if (! empty($teamData['uuid']) && isset($existingTeams[$teamData['uuid']])) {
                $team = $existingTeams[$teamData['uuid']];
                $team->update([
                    'name' => $teamData['name'],
                ]);
                $syncUUIDs[] = $team->uuid;

                continue;
            }

            if (! empty($teamData['uuid']) && ! isset($existingTeams[$teamData['uuid']])) {
                throw new Exception("Invalid team UUID {$teamData['uuid']} for this division");
            }

            $newTeam = $division->teams()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $teamData['name'],
            ]);

            $syncUUIDs[] = $newTeam->uuid;
        }

        $division->teams()
            ->whereNotIn('uuid', $syncUUIDs)
            ->delete();
    }
}
