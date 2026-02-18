<?php

namespace App\Services;

use App\Models\Division;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivisionService
{
    public function index()
    {
        return Division::with(['creator', 'teams'])->latest()->get();
    }

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

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            $division->teams()->withTrashed()->forceDelete();
            $division->forceDelete();

            return true;
        });
    }

    public function getTrashed()
    {
        return Division::onlyTrashed()->With('teams')->latest()->get();
    }

    /**
     * Sync Teams (create, update, delete)
     */
    private function syncTeams(Division $division, array $teams, int $userId): void
    {
        // dd($teams);

        if (empty($teams)) {
            $division->teams()->delete();

            return;
        }

        $existingTeams = $division->teams()->get()->keyBy('uuid');
        $syncUUIDs = [];

        foreach ($teams as $teamData) {

            // UPDATE
            if (! empty($teamData['uuid']) && isset($existingTeams[$teamData['uuid']])) {
                $team = $existingTeams[$teamData['uuid']];
                $team->update([
                    'name' => $teamData['name'],
                ]);
                $syncUUIDs[] = $team->uuid;

                continue;
            }

            // INVALID UUID (security check)
            if (! empty($teamData['uuid']) && ! isset($existingTeams[$teamData['uuid']])) {
                throw new Exception("Invalid team UUID {$teamData['uuid']} for this division");
            }

            // CREATE
            $newTeam = $division->teams()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $teamData['name'],
            ]);

            $syncUUIDs[] = $newTeam->uuid;
        }

        // DELETE teams yang tidak dikirim
        $division->teams()
            ->whereNotIn('uuid', $syncUUIDs)
            ->delete();
    }
}
