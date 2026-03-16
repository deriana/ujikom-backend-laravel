<?php

namespace Database\Factories;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssessmentCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssessmentCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            // Mengambil ID user random atau buat user baru jika belum ada
            'created_by_id' => User::exists() ? User::inRandomOrder()->first()->id : User::factory(),
            'updated_by_id' => null,
        ];
    }

    /**
     * State untuk kategori yang tidak aktif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
