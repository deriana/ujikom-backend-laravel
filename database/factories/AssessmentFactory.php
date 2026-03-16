<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            // Mengambil ID employee secara acak atau membuat baru
            'evaluator_id' => Employee::factory(),
            'evaluatee_id' => Employee::factory(),

            // Format periode YYYY-MM sesuai spesifikasi model
            'period' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m'),

            'note' => $this->faker->sentence(10),

            // uuid tidak perlu diisi di sini karena sudah dihandle oleh boot() di Model
        ];
    }
}
