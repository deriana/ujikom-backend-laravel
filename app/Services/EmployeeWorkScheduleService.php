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
    public function index()
    {
        return EmployeeWorkSchedule::with(['employee', 'workSchedule'])
            ->latest()
            ->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            $priority = isset($data['end_date']) ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            if ($priority === PriorityEnum::LEVEL_1->value) {
                EmployeeWorkSchedule::where('employee_id', $employee->id)
                    ->level1()
                    ->whereNull('end_date')
                    ->update([
                        'end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString(),
                    ]);
            }

            $this->validateDateConflict(
                $employee->id,
                $data['start_date'],
                $data['end_date'] ?? null,
                $priority
            );

            $assignment = new EmployeeWorkSchedule([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'priority' => $priority,
            ]);

            $assignment->customNotification = [
                'title' => 'Work Schedule Assigned',
                'message' => "Work schedule '{$workSchedule->name}' for {$employee->user->name} (NIK: {$employee->nik}) has been assigned from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            $assignment->save();

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    public function update(EmployeeWorkSchedule $assignment, array $data)
    {
        return DB::transaction(function () use ($assignment, $data) {
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            $startDate = $data['start_date'];
            $endDate = $data['end_date'] ?? null;
            $priority = $endDate ? PriorityEnum::LEVEL_2->value : PriorityEnum::LEVEL_1->value;

            $this->validateDateConflict(
                $assignment->employee_id,
                $startDate,
                $endDate,
                $priority,
                $assignment->id
            );

            $assignment->update([
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'priority' => $priority,
            ]);

            // 🔹 Custom notification tanpa URL
            $assignment->customNotification = [
                'title' => 'Work Schedule Updated',
                'message' => "Work schedule '{$workSchedule->name}' for {$assignment->employee->user->name} (NIK: {$assignment->employee->nik}) has been updated from {$assignment->start_date}".($assignment->end_date ? " to {$assignment->end_date}" : ''),
                'url' => null,
            ];

            return $assignment->load(['employee', 'workSchedule']);
        });
    }

    public function delete(EmployeeWorkSchedule $assignment): bool
    {
        return DB::transaction(function () use ($assignment) {

            // 🔹 Custom notification tanpa URL
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
     * Prevent overlapping schedule
     */
    private function validateDateConflict(
        int $employeeId,
        string $startDate,
        ?string $endDate,
        int $priority,
        ?int $ignoreId = null
    ): void {
        // Gunakan scope yang kamu buat tadi!
        $query = EmployeeWorkSchedule::where('employee_id', $employeeId)
            ->where('priority', $priority);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $query->where(function ($q) use ($startDate, $endDate) {
            // Kita pakai bantuan Carbon untuk mempermudah logika 'akhir zaman'
            $actualEnd = $endDate ?? '9999-12-31';

            $q->where(function ($query) use ($startDate, $actualEnd) {
                $query->where('start_date', '<=', $actualEnd)
                    ->where(function ($sub) use ($startDate) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            });
        });

        if ($query->exists()) {
            throw new Exception("Schedule conflict detected for Priority Level $priority");
        }
    }
}
