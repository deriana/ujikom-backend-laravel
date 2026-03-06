<?php

namespace App\Services;

use App\Models\Division;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivisionService
{
    /**
     * Get all divisions with their creator and associated teams.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve divisions with eager loaded relationships
        return Division::with(['creator', 'teams'])->latest()->get();
    }

    /**
     * Store a new division and sync its teams.
     *
     * @param array $data
     * @param int $userId
     * @return Division
     */
    public function store(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Create the division record
            $division = Division::create([
                'name' => $data['name'],
                'code' => $data['code'],
            ]);

            // 2. Sync associated teams if provided
            $this->syncTeams($division, $data['teams'] ?? [], $userId);

            return $division->load('teams');
        });
    }

    /**
     * Update an existing division and its teams.
     *
     * @param Division $division
     * @param array $data
     * @param int $userId
     * @return Division
     * @throws Exception
     */
    public function update(Division $division, array $data, int $userId)
    {
        // 1. Validate if the division can be updated
        if ($division->system_reserve) {
            throw new Exception('Cannot update a system reserve division');
        }
        if ($division->trashed()) {
            throw new Exception('Cannot update a deleted division');
        }

        return DB::transaction(function () use ($division, $data, $userId) {
            // 2. Update division basic information
            $division->update([
                'name' => $data['name'] ?? $division->name,
                'code' => $data['code'] ?? $division->code,
            ]);

            // 3. Sync teams if the key exists in the input data
            if (array_key_exists('teams', $data)) {
                $this->syncTeams($division, $data['teams'] ?? [], $userId);
            }

            return $division->load('teams');
        });
    }

    /**
     * Soft delete a division and its related teams.
     *
     * @param Division $division
     * @return bool
     * @throws Exception
     */
    public function delete(Division $division): bool
    {
        // 1. Security and state validation
        if ($division->system_reserve) {
            throw new Exception('Cannot delete a system reserve division');
        }
        if ($division->trashed()) {
            throw new Exception('Cannot delete a deleted division');
        }

        return DB::transaction(function () use ($division) {
            // 2. Cascade soft delete to teams
            $division->teams()->delete();
            // 3. Soft delete the division
            $division->delete();

            return true;
        });
    }

    /**
     * Restore a soft-deleted division and its teams.
     *
     * @param string $uuid
     * @return Division
     * @throws Exception
     */
    public function restore(string $uuid): Division
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the trashed division
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $division->trashed()) {
                throw new Exception('Division is not deleted');
            }

            // 2. Restore the division and its previously trashed teams
            $division->restore();
            $division->teams()->onlyTrashed()->restore();

            return $division->load('teams');
        });
    }

    /**
     * Permanently delete a division and all its teams.
     *
     * @param string $uuid
     * @return bool
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the division including trashed ones
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            // 2. Force delete all related teams first
            $division->teams()->withTrashed()->forceDelete();
            // 3. Force delete the division
            $division->forceDelete();

            return true;
        });
    }

    /**
     * Get all soft-deleted divisions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        return Division::onlyTrashed()->With('teams')->latest()->get();
    }

    /**
     * Synchronize teams for a division (Create, Update, or Delete).
     *
     * @param Division $division
     * @param array $teams
     * @param int $userId
     * @return void
     */
    private function syncTeams(Division $division, array $teams, int $userId): void
    {
        // 1. If teams array is empty, delete all existing teams for this division
        if (empty($teams)) {
            $division->teams()->delete();

            return;
        }

        $existingTeams = $division->teams()->get()->keyBy('uuid');
        $syncUUIDs = [];

        foreach ($teams as $teamData) {
            // 2. Handle UPDATE for existing teams
            if (! empty($teamData['uuid']) && isset($existingTeams[$teamData['uuid']])) {
                $team = $existingTeams[$teamData['uuid']];
                $team->update([
                    'name' => $teamData['name'],
                ]);
                $syncUUIDs[] = $team->uuid;

                continue;
            }

            // 3. Security check for invalid UUIDs
            if (! empty($teamData['uuid']) && ! isset($existingTeams[$teamData['uuid']])) {
                throw new Exception("Invalid team UUID {$teamData['uuid']} for this division");
            }

            // 4. Handle CREATE for new teams
            $newTeam = $division->teams()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $teamData['name'],
            ]);

            $syncUUIDs[] = $newTeam->uuid;
        }

        // 5. DELETE teams that were not included in the input array
        $division->teams()
            ->whereNotIn('uuid', $syncUUIDs)
            ->delete();
    }
}
