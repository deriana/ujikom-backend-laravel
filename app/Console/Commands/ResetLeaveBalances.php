<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;

class ResetLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:reset-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset leave balances for all employees at the start of a new year';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Fetch all employees except those with the OWNER role
        $employees = Employee::whereHas('user', function ($q) {
            $q->withoutRole(UserRole::OWNER->value);
        })->get();

        // Get active leave types that have a defined quota
        $leaveTypes = LeaveType::where('is_active', true)
            ->whereNotNull('default_days')
            ->get();

        $currentYear = now()->year;
        $balanceCount = 0;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                // Validasi Gender agar balance yang dibuat relevan
                if ($type->gender !== 'all' && $type->gender !== $employee->gender) {
                    continue;
                }

                // Create or update the balance record for the current year
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

                $balanceCount++;
            }
        }

        $this->info("Successfully reset/created {$balanceCount} leave balances for {$currentYear}. (Owner skipped)");
    }
}
