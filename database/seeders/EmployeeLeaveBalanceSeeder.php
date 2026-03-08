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
        $employees = Employee::with('user')->get();
        $leaveTypes = LeaveType::whereNotNull('default_days')->where('is_active', true)->get();
        $currentYear = now()->year;
        $balancesToCreate = [];

        foreach ($employees as $employee) {
            // --- OWNER PROTECTION ---
            // Ensure the related user does not have the 'owner' role
            if ($employee->user && $employee->user->hasRole(\App\Enums\UserRole::OWNER->value)) {
                continue;
            }
            // ------------------------

            foreach ($leaveTypes as $type) {
                if ($type->gender !== 'all' && $type->gender !== $employee->gender) {
                    continue;
                }

                $balancesToCreate[] = [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                    'total_days' => $type->default_days,
                    'used_days' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Use upsert for mass insert/update to improve performance
        EmployeeLeaveBalance::upsert($balancesToCreate,
            ['employee_id', 'leave_type_id', 'year'],
            ['total_days', 'used_days', 'updated_at']);
    }
}
