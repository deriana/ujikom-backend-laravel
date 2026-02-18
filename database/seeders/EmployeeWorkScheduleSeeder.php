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
        // Ambil Jadwal yang sudah dibuat di WorkScheduleSeeder
        $office = WorkSchedule::where('name', 'like', '%Office Regular%')->first();
        $remote = WorkSchedule::where('name', 'like', '%Remote Full%')->first();
        $hybrid = WorkSchedule::where('name', 'like', '%Hybrid Standard%')->first();
        $executive = WorkSchedule::where('name', 'like', '%Executive Flexible%')->first();

        // Ambil Employee berdasarkan NIK yang sudah kita buat di EmployeeSeeder
        $owner = Employee::where('nik', 'OWNER001')->first();
        $director = Employee::where('nik', 'DIR001')->first();
        $finance = Employee::where('nik', 'FIN001')->first();
        $manager = Employee::where('nik', 'EMP20230001')->first();
        $hr = Employee::where('nik', 'EMP20230002')->first();
        $employee = Employee::where('nik', 'EMP20230003')->first();

        // Mapping Jadwal Karyawan
        $assignments = [
            [
                'employee' => $owner,
                'schedule' => $executive, // Owner bebas jam kerja
            ],
            [
                'employee' => $director,
                'schedule' => $executive, // Director juga flexible
            ],
            [
                'employee' => $finance,
                'schedule' => $office,    // Finance biasanya wajib di kantor
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
                        'end_date' => null, // null berarti jadwal aktif selamanya
                    ]
                );
            }
        }
    }
}
