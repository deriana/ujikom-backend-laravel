<?php

namespace App\Services;

use App\Enums\PriorityEnum;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class EmployeeWorkScheduleService
{
    /**
     * Get all employee work schedules with related data.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve all assignments with eager loaded relationships
        return EmployeeWorkSchedule::with(['employee', 'workSchedule'])
            ->latest()
            ->get();
    }

    /**
     * Assign a work schedule to an employee.
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Find employee and work schedule by identifiers
            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            // 2. Determine priority level based on whether it's a temporary (Level 2) or permanent (Level 1) schedule
            $priority = isset($data['end_date']) ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            // 3. If Level 1, close the previous permanent schedule to prevent overlap
            if ($priority === PriorityEnum::LEVEL_1->value) {
                EmployeeWorkSchedule::where('employee_id', $employee->id)
                    ->level1()
                    ->whereNull('end_date')
                    ->update([
                        'end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString(),
                    ]);
            }

            // 4. Validate that the new schedule doesn't conflict with existing ones of the same priority
            $this->validateDateConflict(
                $employee->id,
                $data['start_date'],
                $data['end_date'] ?? null,
                $priority
            );

            // 5. Create the assignment record
            $assignment = new EmployeeWorkSchedule([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'priority' => $priority,
            ]);

            // 6. Set custom notification data
            $assignment->customNotification = [
                'title' => 'Work Schedule Assigned',
                'message' => "Work schedule '{$workSchedule->name}' for {$employee->user->name} (NIK: {$employee->nik}) has been assigned from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            $assignment->save();

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    /**
     * Update an existing employee work schedule assignment.
     */
    public function update(EmployeeWorkSchedule $assignment, array $data)
    {
        return DB::transaction(function () use ($assignment, $data) {
            // 1. Find the requested work schedule
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            // 2. Prepare data and determine priority
            $startDate = $data['start_date'];
            $endDate = $data['end_date'] ?? null;
            $priority = $endDate ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            // 3. Validate date conflicts excluding the current record
            $this->validateDateConflict(
                $assignment->employee_id,
                $startDate,
                $endDate,
                $priority,
                $assignment->id
            );

            // 4. Update the assignment record
            $assignment->update([
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'priority' => $priority,
            ]);

            // 5. Set custom notification data
            $assignment->customNotification = [
                'title' => 'Work Schedule Updated',
                'message' => "Work schedule '{$workSchedule->name}' for {$assignment->employee->user->name} (NIK: {$assignment->employee->nik}) has been updated from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    /**
     * Remove a work schedule assignment.
     */
    public function delete(EmployeeWorkSchedule $assignment): bool
    {
        return DB::transaction(function () use ($assignment) {

            // 1. Set custom notification data before deletion
            $assignment->customNotification = [
                'title' => 'Work Schedule Removed',
                'message' => "Work schedule '{$assignment->workSchedule->name}' for {$assignment->employee->user->name} (NIK: {$assignment->employee->nik}) has been removed from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            $assignment->delete();

            return true;
        });
    }

    /**
     * Prevent overlapping schedules for the same priority level.
     */
    private function validateDateConflict(
        int $employeeId,
        string $startDate,
        ?string $endDate,
        int $priority,
        ?int $ignoreId = null
    ): void {
        // 1. Initialize query for the specific employee and priority
        $query = EmployeeWorkSchedule::where('employee_id', $employeeId)
            ->where('priority', $priority);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        // 2. Check for overlapping date ranges
        $query->where(function ($q) use ($startDate, $endDate) {
            // Use a far-future date to represent null end_date for comparison
            $actualEnd = $endDate ?? '9999-12-31';

            $q->where(function ($query) use ($startDate, $actualEnd) {
                $query->where('start_date', '<=', $actualEnd)
                    ->where(function ($sub) use ($startDate) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            });
        });

        // 3. Throw exception if a conflict is found
        if ($query->exists()) {
            throw new Exception("Schedule conflict detected for Priority Level $priority");
        }
    }
}
