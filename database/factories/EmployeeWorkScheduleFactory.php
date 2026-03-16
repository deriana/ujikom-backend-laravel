<?php

namespace Database\Factories;

use App\Enums\PriorityEnum;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeWorkScheduleFactory extends Factory
{
    protected $model = EmployeeWorkSchedule::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'work_schedule_id' => WorkSchedule::factory(),
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d'),
            'priority' => PriorityEnum::LEVEL_1,
        ];
    }
}
