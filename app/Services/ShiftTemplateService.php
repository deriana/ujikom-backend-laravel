<?php

namespace App\Services;

use App\Models\ShiftTemplate;
use Exception;
use Illuminate\Support\Facades\DB;

class ShiftTemplateService
{
    /**
     * Get all shift templates with creator and usage count.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve templates with eager loaded creator and count of related employee shifts
        return ShiftTemplate::with(['creator'])
            ->withCount('employeeShifts')
            ->latest()
            ->get();
    }

    /**
     * Store a new shift template.
     *
     * @param array $data
     * @return ShiftTemplate
     */
    public function store(array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($data) {
            // 1. Determine if the shift spans across midnight
            $crossDay = $this->calculateCrossDay(
                $data['start_time'],
                $data['end_time']
            );

            // 2. Create the shift template record
            return ShiftTemplate::create([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'cross_day' => $crossDay, // enforced
                'late_tolerance_minutes' => $data['late_tolerance_minutes'] ?? 0,
            ]);
        });
    }

    /**
     * Update an existing shift template.
     *
     * @param ShiftTemplate $shift
     * @param array $data
     * @return ShiftTemplate
     * @throws Exception
     */
    public function update(ShiftTemplate $shift, array $data): ShiftTemplate
    {
        // 1. Prevent updating soft-deleted records
        if ($shift->trashed()) {
            throw new Exception('Cannot update a deleted shift template');
        }

        return DB::transaction(function () use ($shift, $data) {
            // 2. Prepare time data and recalculate cross-day status
            $start = $data['start_time'] ?? $shift->start_time;
            $end = $data['end_time'] ?? $shift->end_time;

            $crossDay = $this->calculateCrossDay($start, $end);

            // 3. Update the template record
            $shift->update([
                'name' => $data['name'] ?? $shift->name,
                'start_time' => $start,
                'end_time' => $end,
                'cross_day' => $crossDay,
                'late_tolerance_minutes' => $data['late_tolerance_minutes'] ?? $shift->late_tolerance_minutes,
            ]);

            return $shift->load(['creator'])->loadCount('employeeShifts');
        });
    }

    /**
     * Soft delete a shift template.
     *
     * @param ShiftTemplate $shift
     * @return bool
     * @throws Exception
     */
    public function delete(ShiftTemplate $shift): bool
    {
        // 1. Validate current state
        if ($shift->trashed()) {
            throw new Exception('Shift template already deleted');
        }

        return DB::transaction(function () use ($shift) {
            // 2. Prevent deletion if the template is currently in use
            if ($shift->employeeShifts()->exists()) {
                throw new Exception('Cannot delete shift template assigned to employees');
            }

            $shift->delete();

            return true;
        });
    }

    /**
     * Restore a soft-deleted shift template.
     *
     * @param string $uuid
     * @return ShiftTemplate
     * @throws Exception
     */
    public function restore(string $uuid): ShiftTemplate
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the trashed record
            $shift = ShiftTemplate::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            // 2. Ensure it is actually deleted before restoring
            if (! $shift->trashed()) {
                throw new Exception('Shift template is not deleted');
            }

            $shift->restore();

            return $shift;
        });
    }

    /**
     * Permanently delete a shift template.
     *
     * @param string $uuid
     * @return bool
     * @throws Exception
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the record including trashed ones
            $shift = ShiftTemplate::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            // 2. Prevent permanent deletion if there is historical assignment data
            if ($shift->employeeShifts()->exists()) {
                throw new Exception('Cannot force delete shift template with assignment history');
            }

            $shift->forceDelete();

            return true;
        });
    }

    /**
     * Get all soft-deleted shift templates.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        // 1. Retrieve only trashed templates with creator info
        return ShiftTemplate::onlyTrashed()
            ->with(['creator'])
            ->latest()
            ->get();
    }

    /**
     * Logic to determine if a shift ends on the next day.
     *
     * @param string $start
     * @param string $end
     * @return bool
     */
    private function calculateCrossDay(string $start, string $end): bool
    {
        // 1. If end time is numerically less than start time, it crosses midnight
        return $end < $start;
    }
}
