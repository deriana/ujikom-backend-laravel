<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\EmployeeWorkSchedule;
use Illuminate\Support\Str;

class EmployeeWorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $office = WorkSchedule::where('name', 'Office Regular')->first();
        $remote = WorkSchedule::where('name', 'Remote Regular')->first();
        $hybrid = WorkSchedule::where('name', 'Hybrid Standard')->first();

        $manager = Employee::where('nik', 'EMP20230001')->first();
        $hr = Employee::where('nik', 'EMP20230002')->first();
        $employee = Employee::where('nik', 'EMP20230003')->first();

        EmployeeWorkSchedule::updateOrCreate(
            ['employee_id' => $manager->id],
            [
                'uuid' => Str::uuid(),
                'work_schedule_id' => $office->id,
                'start_date' => $manager->join_date,
                'end_date' => null,
            ]
        );

        EmployeeWorkSchedule::updateOrCreate(
            ['employee_id' => $hr->id],
            [
                'uuid' => Str::uuid(),
                'work_schedule_id' => $hybrid->id,
                'start_date' => $hr->join_date,
                'end_date' => null,
            ]
        );

        EmployeeWorkSchedule::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'uuid' => Str::uuid(),
                'work_schedule_id' => $remote->id,
                'start_date' => $employee->join_date,
                'end_date' => null,
            ]
        );
    }
}
