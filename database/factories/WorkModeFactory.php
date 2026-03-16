<?php

namespace Database\Factories;

use App\Models\WorkMode;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkModeFactory extends Factory
{
    protected $model = WorkMode::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['WFO', 'WFH', 'Remote', 'Hybrid']),
        ];
    }
}
