<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
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

            // CEK BENTROK UNTUK CREATE
            $this->validateDateConflict(
                $employee->id,
                $data['start_date'],
                $data['end_date'] ?? null
            );

            return EmployeeWorkSchedule::create([
                'employee_id' => $employee->id,
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
            ]);
        });
    }

    public function update(EmployeeWorkSchedule $assignment, array $data)
    {
        return DB::transaction(function () use ($assignment, $data) {
            $employeeId = $assignment->employee_id;
            $workSchedule = WorkSchedule::where('uuid', $data['work_schedule_uuid'])->firstOrFail();

            // 1. Tentukan tanggal baru
            $startDate = $data['start_date'];
            $endDate = $data['end_date'] ?? null;

            // 2. Cek bentrok tapi **abaikan record lama yang sedang diupdate**
            $this->validateDateConflict(
                $employeeId,
                $startDate,
                $endDate,
                $assignment->id
            );

            // 3. Tutup record lama (set end_date ke startDate-1)
            $assignment->update(['end_date' => now()->toDateString()]);

            // 4. Buat record baru
            return EmployeeWorkSchedule::create([
                'employee_id' => $employeeId,
                'work_schedule_id' => $workSchedule->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ])->load(['employee', 'workSchedule']);
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
