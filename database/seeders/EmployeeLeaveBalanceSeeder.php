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
        $currentYear = now()->year;

        foreach ($employees as $employee) {
            // --- OWNER PROTECTION ---
            // Ensure the related user does not have the 'owner' role
            if ($employee->user->hasRole(\App\Enums\UserRole::OWNER->value)) {
                continue;
            }
            // ------------------------

            foreach ($leaveTypes as $type) {
                if ($type->default_days === null) {
                    continue;
                }

                if ($type->gender !== 'all' && $type->gender !== $employee->gender) {
                    continue;
                }

                EmployeeLeaveBalance::updateOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $currentYear],
                    ['total_days' => $type->default_days, 'used_days' => 0]
                );
            }
        }
    }
}
