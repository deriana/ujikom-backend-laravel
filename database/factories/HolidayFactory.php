<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 year');
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->words(3, true),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $startDate->format('Y-m-d'), // Libur 1 hari default
            'is_recurring' => $this->faker->boolean(),
            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),
        ];
    }
}
