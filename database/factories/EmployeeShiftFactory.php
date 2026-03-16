<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeShift>
 */
class EmployeeShiftFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     *
     * @var string
     */
    protected $model = EmployeeShift::class;

    /**
     * Mendefinisikan status default untuk model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'employee_id' => Employee::factory(), // Membuat employee baru otomatis
            'shift_template_id' => ShiftTemplate::factory(), // Membuat template shift baru otomatis
            'shift_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'created_by_id' => User::factory(), // User yang membuat data
            'updated_by_id' => User::factory(), // User yang mengubah data
        ];
    }
}
