<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeLeaveBalanceFactory extends Factory
{
    protected $model = EmployeeLeaveBalance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => date('Y'),
            'total_days' => 12,
            'used_days' => $this->faker->numberBetween(0, 5),
        ];
    }
}
