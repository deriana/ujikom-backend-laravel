<?php

namespace App\Services;

use App\Models\Allowance;
use App\Models\Position;
use Exception;
use Illuminate\Support\Facades\DB;

class PositionService
{
    public function index()
    {
        return Position::with(['creator', 'allowances'])->latest()->get();
    }

    public function store(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {

            $position = Position::create([
                'name' => $data['name'],
                'base_salary' => $data['base_salary'],
                'created_by_id' => $userId,
            ]);

            $this->syncAllowances($position, $data);

            return $position->load('allowances');
        });
    }

    public function update(Position $position, array $data, int $userId)
    {
        if ($position->trashed()) {
            throw new Exception('Cannot update a deleted position');
        }

        return DB::transaction(function () use ($position, $data) {

            $position->update([
                'name'   => $data['name']   ?? $position->name,
                'base_salary' => $data['base_salary'] ?? $position->amount,
            ]);

            $this->syncAllowances($position, $data);

            return $position->load('allowances');
        });
    }

    public function delete(Position $position): bool
    {
        if ($position->trashed()) {
            throw new Exception('Cannot delete a deleted position');
        }

        return DB::transaction(fn () => $position->delete());
    }

    public function restore(string $uuid): Position
    {
        return DB::transaction(function () use ($uuid) {

            $position = Position::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $position->trashed()) {
                throw new Exception('Position is not deleted');
            }

            $position->restore();

            return $position;
        });
    }

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $position = Position::withTrashed()->whereUuid($uuid)->firstOrFail();
            $position->forceDelete();

            return true;
        });
    }

    /**
     * Sync allowances pivot (UUID → ID mapping)
     */
    private function syncAllowances(Position $position, array $data): void
    {
        if (! array_key_exists('allowances', $data)) {
            return;
        }

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
            if (! isset($allowances[$item['uuid']])) {
                throw new Exception("Allowance UUID {$item['uuid']} not found");
            }

            $syncData[$allowances[$item['uuid']]->id] = [
                'amount' => $item['amount'] ?? null,
            ];
        }

        $position->allowances()->sync($syncData);
    }
}
