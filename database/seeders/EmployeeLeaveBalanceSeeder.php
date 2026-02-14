<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class EmployeeLeaveBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $leaveTypes = LeaveType::all();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                // hanya buat balance jika default_days ada
                if ($type->default_days !== null) {
                    EmployeeLeaveBalance::updateOrCreate(
                        ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => now()->year],
                        ['total_days' => $type->default_days, 'used_days' => 0]
                    );
                }
            }
        }
    }
}
