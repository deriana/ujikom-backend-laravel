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
        // Get schedules created in WorkScheduleSeeder
        $office = WorkSchedule::where('name', 'like', '%Office Regular%')->first();
        $remote = WorkSchedule::where('name', 'like', '%Remote Full%')->first();
        $hybrid = WorkSchedule::where('name', 'like', '%Hybrid Standard%')->first();
        $executive = WorkSchedule::where('name', 'like', '%Executive Flexible%')->first();

        // Get Employees by NIK created in EmployeeSeeder
        $owner = Employee::where('nik', 'OWNER001')->first();
        $director = Employee::where('nik', 'DIR001')->first();
        $finance = Employee::where('nik', 'FIN001')->first();
        $manager = Employee::where('nik', 'EMP20230001')->first();
        $hr = Employee::where('nik', 'EMP20230002')->first();
        $employee = Employee::where('nik', 'EMP20230003')->first();

        // Mapping Employee Schedules
        $assignments = [
            [
                'employee' => $owner,
                'schedule' => $executive, // Owner has flexible working hours
            ],
            [
                'employee' => $director,
                'schedule' => $executive, // Director is also flexible
            ],
            [
                'employee' => $finance,
                'schedule' => $office,    // Finance is usually required to be in the office
            ],
            [
                'employee' => $manager,
                'schedule' => $office,
            ],
            [
                'employee' => $hr,
                'schedule' => $hybrid,
            ],
            [
                'employee' => $employee,
                'schedule' => $remote,
            ],
        ];

        foreach ($assignments as $assignment) {
            if ($assignment['employee'] && $assignment['schedule']) {
                EmployeeWorkSchedule::updateOrCreate(
                    ['employee_id' => $assignment['employee']->id],
                    [
                        'uuid' => Str::uuid(),
                        'work_schedule_id' => $assignment['schedule']->id,
                        'start_date' => $assignment['employee']->join_date,
                        'end_date' => null, // null means the schedule is active indefinitely
                    ]
                );
            }
        }
    }
}
