<?php

namespace App\Services;

use App\Models\Allowance;
use Illuminate\Support\Facades\DB;
use Exception;

class AllowanceService
{
    public function index()
    {
        return Allowance::with(['creator', 'positions'])
            ->latest()
            ->get();
    }

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

    public function delete(Allowance $allowance): bool
    {
        if ($allowance->trashed()) {
            throw new Exception('Cannot delete a deleted allowance');
        }

        return DB::transaction(fn () => $allowance->delete());
    }

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

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $allowance = Allowance::withTrashed()->whereUuid($uuid)->firstOrFail();
            return (bool) $allowance->forceDelete();
        });
    }

    public function getTrashed()
    {
        return Allowance::onlyTrashed()->latest()->get();
    }
}
