<?php

namespace App\Services;

use App\Models\EmployeeWorkSchedule;
use Exception;
use Illuminate\Support\Facades\DB;

class EmployeeWorkScheduleService
{
    public function index()
    {
        return EmployeeWorkSchedule::with(['employee', 'workSchedule'])
            ->latest()
            ->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            $this->validateDateConflict(
                $data['employee_id'],
                $data['start_date'],
                $data['end_date'] ?? null
            );

            return EmployeeWorkSchedule::create($data);
        });
    }

    public function update(EmployeeWorkSchedule $assignment, array $data)
    {
    
        return DB::transaction(function () use ($assignment, $data) {

            $this->validateDateConflict(
                $assignment->employee_id,
                $data['start_date'] ?? $assignment->start_date,
                $data['end_date'] ?? $assignment->end_date,
                $assignment->id
            );

            $assignment->update($data);

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    public function delete(EmployeeWorkSchedule $assignment): bool
    {
        return DB::transaction(function () use ($assignment) {
            $assignment->delete();
            return true;
        });
    }

    /**
     * Prevent overlapping schedule
     */
    private function validateDateConflict(
        int $employeeId,
        string $startDate,
        ?string $endDate,
        ?int $ignoreId = null
    ): void {

        $query = EmployeeWorkSchedule::where('employee_id', $employeeId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $query->where(function ($q) use ($startDate, $endDate) {

            $q->whereBetween('start_date', [$startDate, $endDate ?? $startDate])
              ->orWhereBetween('end_date', [$startDate, $endDate ?? $startDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where(function ($q3) use ($startDate, $endDate) {
                         $q3->whereNull('end_date')
                            ->orWhere('end_date', '>=', $endDate ?? $startDate);
                     });
              });
        });

        if ($query->exists()) {
            throw new Exception('Schedule conflict detected for this employee');
        }
    }
}
