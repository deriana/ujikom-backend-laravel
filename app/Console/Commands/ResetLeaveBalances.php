<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;

class ResetLeaveBalances extends Command
{
    protected $signature = 'leave:reset-balances';

    protected $description = 'Reset leave balances for all employees at the start of a new year';

    public function handle()
    {
        // --- FILTER: KECUALI OWNER ---
        // Kita hanya ambil employee yang usernya BUKAN Owner
        $employees = Employee::whereHas('user', function ($q) {
            $q->withoutRole(UserRole::OWNER->value);
        })->get();
        // -----------------------------

        $leaveTypes = LeaveType::where('is_active', true)
            ->whereNotNull('default_days') // Hanya yang punya kuota
            ->get();

        $currentYear = now()->year;
        $count = 0;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                // Validasi Gender agar balance yang dibuat relevan
                if ($type->gender !== 'all' && $type->gender !== $employee->gender) {
                    continue;
                }

                $balance = EmployeeLeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $currentYear,
                    ],
                    [
                        'total_days' => $type->default_days,
                        'used_days' => 0,
                    ]
                );

                $count++;
            }
        }

        $this->info("Successfully reset/created {$count} leave balances for {$currentYear}. (Owner skipped)");
    }
}
