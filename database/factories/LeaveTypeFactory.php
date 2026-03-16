<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            // Tambahkan kata acak agar selalu unik meski pilihan utamanya terbatas
            'name' => $this->faker->unique()->words(3, true).' '.$this->faker->numberBetween(1, 9999),
            'is_active' => true,
            'default_days' => 12,
            'gender' => $this->faker->randomElement(['all', 'male', 'female']),
            'requires_family_status' => false,
            'is_unlimited' => false,
            'description' => $this->faker->sentence(),
            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),
        ];
    }
}
