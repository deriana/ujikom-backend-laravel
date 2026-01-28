<?php

namespace App\Services;

use App\Models\Allowance;
use Exception;
use Illuminate\Support\Facades\DB;

class AllowanceService
{
    public function index()
    {
        try {
            return Allowance::with(['creator'])
                ->latest()
                ->get();

        } catch (Exception $e) {
            throw new Exception('Failed to fetch Allowance'.$e->getMessage());
        }
    }

    public function store(array $data, int $userId)
    {
        DB::beginTransaction();

        try {
            $allowance = Allowance::create([
                'name' => $data['name'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'created_by_id' => $userId,
            ]);

            DB::commit();

            return $allowance;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to create allowance: '.$e->getMessage());
        }
    }

    public function update(Allowance $allowance, array $data, int $userId)
    {
        if ($allowance->trashed()) {
            throw new Exception('Cannot update a deleted allowance');
        }

        DB::beginTransaction();

        try {
            $allowance->update([
                'name' => $data['name'] ?? $allowance->name,
                'amount' => $data['amount'] ?? $allowance->amount,
                'type' => $data['type'] ?? $allowance->type,
            ]);
            DB::commit();

            return $allowance;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to update allowance'.$e->getMessage());
        }
    }

    public function delete(Allowance $allowance)
    {
        DB::beginTransaction();

        if ($allowance->trashed()) {
            throw new Exception('Cannot delete a deleted allowance');
        }

        try {
            $allowance->delete();

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to soft delete allowance with : '.$e->getMessage());
        }
    }

    public function restore(string $uuid)
    {
        try {
            $allowance = Allowance::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $allowance->trashed()) {
                throw new Exception('Allowance is not deleted');
            }

            DB::beginTransaction();

            $allowance->restore();

            DB::commit();

            return $allowance;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to restore allowance with : '.$e->getMessage());
        }
    }

    public function forceDelete(string $uuid)
    {
        DB::beginTransaction();

        try {
            $allowance = Allowance::withTrashed()->whereUuid($uuid)->firstOrFail();

            $allowance->withTrashed()->forceDelete();

            $allowance->forceDelete();

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to permanently delete allowance with : '.$e->getMessage());
        }
    }
}
