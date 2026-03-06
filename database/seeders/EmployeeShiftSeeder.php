<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmployeeShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $creatorId = User::first()->id;

        // Get Shift Templates created in ShiftTemplateSeeder
        $shiftPagi = ShiftTemplate::where('name', 'like', '%Shift Regular%')->first();
        $shiftMalam = ShiftTemplate::where('name', 'like', '%Shift Malam%')->first();
        $shiftSore = ShiftTemplate::where('name', 'like', '%Shift Sore%')->first();

        // Get Employees by NIK for accuracy
        $finance = Employee::where('nik', 'FIN001')->first();
        $manager = Employee::where('nik', 'EMP20230001')->first();
        $hr = Employee::where('nik', 'EMP20230002')->first();
        $staff = Employee::where('nik', 'EMP20230003')->first();

        // Create schedule for the next 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i)->toDateString();

            // Define assignment list for that day
            $assignments = [
                ['employee' => $finance, 'template' => $shiftPagi],
                ['employee' => $manager, 'template' => $shiftPagi],
                ['employee' => $hr, 'template' => $shiftSore], // HR sesekali shift sore
                // ['employee' => $staff, 'template' => $shiftPagi], // Staff (Tesla) kena shift malam
            ];

            foreach ($assignments as $assign) {
                if ($assign['employee'] && $assign['template']) {
                    EmployeeShift::create([
                        'uuid' => Str::uuid(),
                        'employee_id' => $assign['employee']->id,
                        'shift_template_id' => $assign['template']->id,
                        'shift_date' => $date,
                        'created_by_id' => $creatorId,
                    ]);
                }
            }
        }
    }
}
