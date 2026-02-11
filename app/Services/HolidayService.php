<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Exception;

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
            return Holiday::create([
                'name' => $data['name'],
                'date' => $data['date'],
                'is_recurring' => $data['is_recurring'],
            ]);
        });
    }

    public function update(Holiday $holiday, array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($holiday, $data) {
            $holiday->update([
                'name'   => $data['name']   ?? $holiday->name,
                'date'   => $data['date']   ?? $holiday->date,
                'is_recurring'   => $data['is_recurring']   ?? $holiday->is_recurring,
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
