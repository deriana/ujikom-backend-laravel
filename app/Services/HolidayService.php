<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    public function index()
    {
        return Holiday::with(['creator'])
            ->latest()
            ->get();
    }

    public function store(array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($data, $userId) {

            $startDate = $data['start_date'];
            $endDate   = $data['end_date'] ?? null;

            return Holiday::create([
                'name'          => $data['name'],
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'is_recurring'  => $data['is_recurring'] ?? false,
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ]);
        });
    }

    public function update(Holiday $holiday, array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($holiday, $data, $userId) {

            $holiday->update([
                'name'          => $data['name'] ?? $holiday->name,
                'start_date'    => $data['start_date'] ?? $holiday->start_date,
                'end_date'      => array_key_exists('end_date', $data)
                                    ? $data['end_date']
                                    : $holiday->end_date,
                'is_recurring'  => $data['is_recurring'] ?? $holiday->is_recurring,
                'updated_by_id' => $userId,
            ]);

            return $holiday;
        });
    }

    public function delete(Holiday $holiday): bool
    {
        return DB::transaction(function () use ($holiday) {
            return (bool) $holiday->delete();
        });
    }
}
