<?php

namespace App\Services;

use App\Models\ShiftTemplate;
use Exception;
use Illuminate\Support\Facades\DB;

class ShiftTemplateService
{
    public function index()
    {
        return ShiftTemplate::with(['creator'])
            ->withCount('employeeShifts')
            ->latest()
            ->get();
    }

    public function store(array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($data) {

            $crossDay = $this->calculateCrossDay(
                $data['start_time'],
                $data['end_time']
            );

            return ShiftTemplate::create([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'cross_day' => $crossDay, // enforced
                'late_tolerance_minutes' => $data['late_tolerance_minutes'] ?? 0,
            ]);
        });
    }

    public function update(ShiftTemplate $shift, array $data): ShiftTemplate
    {
        if ($shift->trashed()) {
            throw new Exception('Cannot update a deleted shift template');
        }

        return DB::transaction(function () use ($shift, $data) {

            $start = $data['start_time'] ?? $shift->start_time;
            $end = $data['end_time'] ?? $shift->end_time;

            $crossDay = $this->calculateCrossDay($start, $end);

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

    public function delete(ShiftTemplate $shift): bool
    {
        if ($shift->trashed()) {
            throw new Exception('Shift template already deleted');
        }

        return DB::transaction(function () use ($shift) {

            if ($shift->employeeShifts()->exists()) {
                throw new Exception('Cannot delete shift template assigned to employees');
            }

            $shift->delete();

            return true;
        });
    }

    public function restore(string $uuid): ShiftTemplate
    {
        return DB::transaction(function () use ($uuid) {

            $shift = ShiftTemplate::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            if (! $shift->trashed()) {
                throw new Exception('Shift template is not deleted');
            }

            $shift->restore();

            return $shift;
        });
    }

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $shift = ShiftTemplate::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            if ($shift->employeeShifts()->exists()) {
                throw new Exception('Cannot force delete shift template with assignment history');
            }

            $shift->forceDelete();

            return true;
        });
    }

    public function getTrashed()
    {
        return ShiftTemplate::onlyTrashed()
            ->with(['creator'])
            ->latest()
            ->get();
    }

    private function calculateCrossDay(string $start, string $end): bool
    {
        return $end < $start;
    }
}
