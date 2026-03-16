<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle().' '.$this->faker->word().' '.$this->faker->numberBetween(1, 100000),
            'base_salary' => $this->faker->numberBetween(5000000, 15000000),
            'system_reserve' => false,

            // Audit Trail
            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),

            // uuid otomatis di-generate oleh boot() model
        ];
    }

    /**
     * State untuk data yang diproteksi sistem
     */
    public function system()
    {
        return $this->state(fn (array $attributes) => [
            'system_reserve' => true,
        ]);
    }
}
