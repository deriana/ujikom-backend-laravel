<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    /**
     * Get all holidays with their creator information.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve holidays with eager loaded creator relationship
        return Holiday::with(['creator'])
            ->latest()
            ->get();
    }

    /**
     * Store a new holiday record.
     *
     * @param array $data
     * @param int $userId
     * @return Holiday
     */
    public function store(array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Prepare date variables
            $startDate = $data['start_date'];
            $endDate   = $data['end_date'] ?? null;

            // 2. Create the holiday record in the database
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

    /**
     * Update an existing holiday record.
     *
     * @param Holiday $holiday
     * @param array $data
     * @param int $userId
     * @return Holiday
     */
    public function update(Holiday $holiday, array $data, int $userId): Holiday
    {
        return DB::transaction(function () use ($holiday, $data, $userId) {
            // 1. Update the holiday attributes with provided data or keep existing values
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

    /**
     * Delete a holiday record.
     *
     * @param Holiday $holiday
     * @return bool
     */
    public function delete(Holiday $holiday): bool
    {
        return DB::transaction(function () use ($holiday) {
            // 1. Perform the deletion of the holiday record
            return (bool) $holiday->delete();
        });
    }
}
