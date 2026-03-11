<?php

namespace Database\Factories;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftTemplateFactory extends Factory
{
    protected $model = ShiftTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Shift Pagi', 'Shift Malam', 'Shift Middle']),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'cross_day' => false,
            'late_tolerance_minutes' => 15,
            'created_by_id' => User::factory(),
        ];
    }
}
