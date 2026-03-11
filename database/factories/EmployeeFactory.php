<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use App\Models\Team;
use App\Models\Position;
use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            // NIK otomatis digenerate oleh booted() di model jika kosong,
            // tapi kita bisa mengisinya di sini jika ingin spesifik.
            'nik' => null,

            'user_id' => User::factory(),
            'team_id' => Team::factory(), // Pastikan TeamFactory sudah ada
            'position_id' => Position::factory(), // Pastikan PositionFactory sudah ada

            'employee_status' => $this->faker->randomElement([
                EmployeeStatus::PERMANENT,
                EmployeeStatus::CONTRACT,
                EmployeeStatus::PROBATION
            ]),

            'gender' => $this->faker->randomElement(['male', 'female']),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'date_of_birth' => $this->faker->date('Y-m-d', '-20 years'),
            'join_date' => $this->faker->date(),
            'base_salary' => $this->faker->numberBetween(5000000, 15000000),

            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),
        ];
    }
}
