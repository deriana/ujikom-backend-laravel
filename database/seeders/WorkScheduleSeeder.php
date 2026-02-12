<?php

namespace Database\Seeders;

use App\Models\WorkMode;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $wfo = WorkMode::where('name', 'WFO')->first();
        $wfh = WorkMode::where('name', 'WFH')->first();
        $hybrid = WorkMode::where('name', 'HYBRID')->first();

        $schedules = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Office Regular',
                'work_mode_id' => $wfo->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'requires_office_location' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Remote Regular',
                'work_mode_id' => $wfh->id,
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
                'requires_office_location' => false,
            ],
            [
                'uuid' => Str::uuid(),
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
