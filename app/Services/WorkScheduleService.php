<?php

namespace App\Services;

use App\Models\WorkSchedule;
use Exception;
use Illuminate\Support\Facades\DB;

class WorkScheduleService
{
    public function index()
    {
        return WorkSchedule::with('workMode', 'employeeWorkSchedules')->latest()->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            return WorkSchedule::create([
                'name' => $data['name'],
                'work_mode_id' => $data['work_mode_id'],
                'work_start_time' => $data['work_start_time'],
                'work_end_time' => $data['work_end_time'],
                'requires_office_location' => $data['requires_office_location'],
            ]);
        });
    }

    public function update(WorkSchedule $schedule, array $data)
    {
        if ($schedule->trashed()) {
            throw new Exception('Cannot update a deleted work schedule');
        }

        return DB::transaction(function () use ($schedule, $data) {

            $schedule->update([
                'name' => $data['name'] ?? $schedule->name,
                'work_mode_id' => $data['work_mode_id'] ?? $schedule->work_mode_id,
                'work_start_time' => $data['work_start_time'] ?? $schedule->work_start_time,
                'work_end_time' => $data['work_end_time'] ?? $schedule->work_end_time,
                'requires_office_location' => $data['requires_office_location'] ?? $schedule->requires_office_location,
            ]);

            return $schedule->load('workMode');
        });
    }

    public function delete(WorkSchedule $schedule): bool
    {
        if ($schedule->trashed()) {
            throw new Exception('Work schedule already deleted');
        }

        return DB::transaction(function () use ($schedule) {

            if ($schedule->employeeWorkSchedules()->exists()) {
                throw new Exception('Cannot delete schedule that is assigned to employees');
            }

            $schedule->delete();

            return true;
        });
    }

    public function restore(string $uuid): WorkSchedule
    {
        return DB::transaction(function () use ($uuid) {

            $schedule = WorkSchedule::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            if (! $schedule->trashed()) {
                throw new Exception('Work schedule is not deleted');
            }

            $schedule->restore();

            return $schedule;
        });
    }

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $schedule = WorkSchedule::withTrashed()
                ->whereUuid($uuid)
                ->firstOrFail();

            if ($schedule->employeeWorkSchedules()->exists()) {
                throw new Exception('Cannot force delete schedule that has assignment history');
            }

            $schedule->forceDelete();

            return true;
        });
    }

    public function getTrashed()
    {
        return WorkSchedule::onlyTrashed()
            ->with('workMode')
            ->latest()
            ->get();
    }
}
