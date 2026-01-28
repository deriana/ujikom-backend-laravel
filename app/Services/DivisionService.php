<?php

namespace App\Services;

use App\Models\Division;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivisionService
{
    public function index($withTeams = true)
    {
        try {
            $query = Division::query();

            if ($withTeams) {
                $query->with('teams:id,division_id,name');
            }

            return $query->latest()->get();

        } catch (Exception $e) {
            throw new Exception('Failed to fetch divisions: '.$e->getMessage());
        }
    }

    public function store(array $data, int $userId)
    {
        DB::beginTransaction();

        try {
            $division = Division::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'created_by_id' => $userId,
            ]);

            if (! empty($data['teams']) && is_array($data['teams'])) {
                foreach ($data['teams'] as $teamName) {
                    $division->teams()->create([
                        'name' => $teamName,
                        'division_id' => $division->id,
                        'created_by_id' => $userId,
                    ]);
                }
            }

            DB::commit();

            return $division->load('teams:id,division_id,name');

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to create division with teams: '.$e->getMessage());
        }
    }

    public function update(Division $division, array $data, int $userId)
    {
        if ($division->trashed()) {
            throw new Exception('Cannot update a deleted division');
        }

        DB::beginTransaction();

        try {
            $division->update([
                'name' => $data['name'] ?? $division->name,
                'code' => $data['code'] ?? $division->code,
            ]);

            if (! empty($data['teams']) && is_array($data['teams'])) {

                $existingTeams = $division->teams()->get(['id', 'uuid', 'name']);
                $existingTeamIds = $existingTeams->pluck('id')->toArray();
                $existingTeamUUIDs = $existingTeams->pluck('uuid')->toArray();

                $sentTeamIds = [];

                foreach ($data['teams'] as $teamData) {

                    if (! empty($teamData['uuid']) && in_array($teamData['uuid'], $existingTeamUUIDs)) {

                        $team = $division->teams()->where('uuid', $teamData['uuid'])->first();

                        if ($team) {
                            $team->update([
                                'name' => $teamData['name'],
                            ]);
                            $sentTeamIds[] = $team->id;
                        }

                    } else {
                        $newTeam = $division->teams()->create([
                            'uuid' => Str::uuid(),
                            'name' => $teamData['name'],
                            'division_id' => $division->id,
                            'created_by_id' => $userId,
                        ]);
                        $sentTeamIds[] = $newTeam->id;
                    }
                }

                $teamsToDelete = array_diff($existingTeamIds, $sentTeamIds);

                if (! empty($teamsToDelete)) {
                    $division->teams()->whereIn('id', $teamsToDelete)->delete();
                }
            }

            DB::commit();

            return $division->load('teams:id,division_id,name,uuid');

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to update division with teams: '.$e->getMessage());
        }
    }

    public function delete(Division $division)
    {
        DB::beginTransaction();

        if ($division->trashed()) {
            throw new Exception('Cannot delete a deleted division');
        }

        //     $blockedUsers = $division->teams()
        //     ->with('users')
        //     ->get()
        //     ->pluck('users')
        //     ->flatten()
        //     ->where('cannot_be_deleted', true);

        // if ($blockedUsers->count() > 0) {
        //     throw new Exception('Cannot delete division because it has users that cannot be deleted.');
        // }

        try {
            if ($division->teams()->exists()) {
                $division->teams()->delete();
            }

            $division->delete();

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to soft delete division with teams: '.$e->getMessage());
        }
    }

    public function restore(string $uuid)
    {
        try {
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $division->trashed()) {
                throw new Exception('Division is not deleted');
            }

            DB::beginTransaction();

            // Restore division
            $division->restore();

            // Restore all soft-deleted teams of this division
            $division->teams()->onlyTrashed()->restore();

            DB::commit();

            return $division->load('teams:id,division_id,name');

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to restore division with teams: '.$e->getMessage());
        }
    }

    public function forceDelete(string $uuid)
    {
        DB::beginTransaction();

        try {
            $division = Division::withTrashed()->whereUuid($uuid)->firstOrFail();

            $division->teams()->withTrashed()->forceDelete();

            $division->forceDelete();

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to permanently delete division with teams: '.$e->getMessage());
        }
    }
}
