<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShiftTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 10,
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '13:00:00',
                'end_time' => '21:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 10,
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'cross_day' => true,
                'late_tolerance_minutes' => 15,
            ],
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::create([
                'uuid' => Str::uuid(),
                ...$shift,
            ]);
        }
    }
}
