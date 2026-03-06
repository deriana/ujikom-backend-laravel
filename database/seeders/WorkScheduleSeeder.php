<?php

namespace Database\Seeders;

use App\Models\WorkMode;
use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $wfo = WorkMode::where('name', 'WFO')->first();
        $wfh = WorkMode::where('name', 'WFH')->first();
        $hybrid = WorkMode::where('name', 'HYBRID')->first();
        $creatorId = User::first()->id;

        $schedules = [
            // --- REGULAR SCHEDULES ---
            [
                'name' => 'Office Regular (08-17)',
                'work_mode_id' => $wfo->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'requires_office_location' => true,
            ],
            [
                'name' => 'Remote Full (WFH)',
                'work_mode_id' => $wfh->id,
                'work_start_time' => '09:00:00',
                'work_end_time' => '18:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'requires_office_location' => false,
            ],
            [
                'name' => 'Hybrid Standard',
                'work_mode_id' => $hybrid->id,
                'work_start_time' => '08:30:00',
                'work_end_time' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'requires_office_location' => false,
            ],

            // --- SHIFT SCHEDULES (SUITABLE FOR OPS/SECURITY/IT) ---
            [
                'name' => 'Afternoon Shift (14-22)',
                'work_mode_id' => $wfo->id,
                'work_start_time' => '14:00:00',
                'work_end_time' => '22:00:00',
                'break_start_time' => '18:00:00',
                'break_end_time' => '19:00:00',
                'requires_office_location' => true,
            ],
            [
                'name' => 'Night Shift (22-06)',
                'work_mode_id' => $wfo->id,
                'work_start_time' => '22:00:00',
                'work_end_time' => '06:00:00',
                'break_start_time' => '01:00:00',
                'break_end_time' => '02:00:00',
                'requires_office_location' => true,
            ],

            // --- EXECUTIVE SCHEDULES (FLEXIBLE) ---
            [
                'name' => 'Executive Flexible',
                'work_mode_id' => $hybrid->id,
                'work_start_time' => '00:00:00', // Start anytime
                'work_end_time' => '23:59:59',
                'break_start_time' => null,
                'break_end_time' => null,
                'requires_office_location' => false,
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::updateOrCreate(
                ['name' => $schedule['name']],
                array_merge($schedule, [
                    'uuid' => Str::uuid(),
                    'created_by_id' => $creatorId,
                ])
            );
        }
    }
}
