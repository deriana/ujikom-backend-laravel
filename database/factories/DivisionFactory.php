<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle() . ' Division',
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'system_reserve' => false,
            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),
            'deleted_by_id' => null,
        ];
    }
}
