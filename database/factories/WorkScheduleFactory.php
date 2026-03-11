<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use App\Models\WorkMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        $schedules = [
            ['name' => 'Jadwal Normal WFO', 'start' => '08:00:00', 'end' => '17:00:00', 'office' => true],
            ['name' => 'Shift Pagi (Early)', 'start' => '07:00:00', 'end' => '16:00:00', 'office' => true],
            ['name' => 'Shift Siang', 'start' => '13:00:00', 'end' => '22:00:00', 'office' => true],
            ['name' => 'Remote Flexible', 'start' => '09:00:00', 'end' => '18:00:00', 'office' => false],
            ['name' => 'Full WFH Standard', 'start' => '08:30:00', 'end' => '17:30:00', 'office' => false],
        ];

        $selected = $this->faker->randomElement($schedules);

        return [
            'name' => $selected['name'],
            'work_mode_id' => WorkMode::factory(),
            'work_start_time' => $selected['start'],
            'work_end_time' => $selected['end'],
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'late_tolerance_minutes' => $this->faker->randomElement([0, 5, 10, 15, 30]),
            'requires_office_location' => $selected['office'],
            'created_by_id' => User::factory(),
        ];
    }
}
