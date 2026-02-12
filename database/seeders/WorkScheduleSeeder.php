<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;
use App\Models\WorkMode;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $wfo = WorkMode::where('name', 'WFO')->first();
        $wfh = WorkMode::where('name', 'WFH')->first();
        $hybrid = WorkMode::where('name', 'HYBRID')->first();

        $schedules = [
            [
                'name' => 'Office Regular',
                'work_mode_id' => $wfo->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'requires_office_location' => true,
            ],
            [
                'name' => 'Remote Regular',
                'work_mode_id' => $wfh->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'requires_office_location' => false,
            ],
            [
                'name' => 'Hybrid Standard',
                'work_mode_id' => $hybrid->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'requires_office_location' => false,
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::updateOrCreate(
                ['name' => $schedule['name']],
                $schedule
            );
        }
    }
}
