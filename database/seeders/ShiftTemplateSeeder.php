<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShiftTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creatorId = User::first()->id;

        $shifts = [
            [
                'name' => 'Shift Pagi (Early)',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 15,
            ],
            [
                'name' => 'Shift Regular',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 10,
            ],
            [
                'name' => 'Shift Sore',
                'start_time' => '15:00:00',
                'end_time' => '23:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 15,
            ],
            [
                'name' => 'Shift Malam (Overnight)',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'cross_day' => true,
                'late_tolerance_minutes' => 15,
            ],
            [
                'name' => 'Shift Long Day (Security)',
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'cross_day' => false,
                'late_tolerance_minutes' => 30,
            ],
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::create([
                'uuid' => Str::uuid(),
                'name' => $shift['name'],
                'start_time' => $shift['start_time'],
                'end_time' => $shift['end_time'],
                'cross_day' => $shift['cross_day'],
                'late_tolerance_minutes' => $shift['late_tolerance_minutes'],
                'created_by_id' => $creatorId,
            ]);
        }
    }
}
