<?php

namespace Database\Seeders;

use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
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

        $shiftPagi = ShiftTemplate::where('name', 'Shift Pagi')->first();
        $shiftMalam = ShiftTemplate::where('name', 'Shift Malam')->first();

        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i)->toDateString();

            EmployeeShift::create([
                'uuid' => Str::uuid(),
                'employee_id' => 1,
                'shift_template_id' => $shiftPagi->id,
                'shift_date' => $date,
            ]);

            EmployeeShift::create([
                'uuid' => Str::uuid(),
                'employee_id' => 2,
                'shift_template_id' => $shiftMalam->id,
                'shift_date' => $date,
            ]);

            EmployeeShift::create([
                'uuid' => Str::uuid(),
                'employee_id' => 3,
                'shift_template_id' => $shiftPagi->id,
                'shift_date' => $date,
            ]);
        }

    }
}
