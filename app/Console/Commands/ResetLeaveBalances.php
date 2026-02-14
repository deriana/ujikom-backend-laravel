<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveBalance;

class ResetLeaveBalances extends Command
{
    protected $signature = 'leave:reset-balances';
    protected $description = 'Reset leave balances for all employees at the start of a new year';

    public function handle()
    {
        $employees = Employee::all();
        $leaveTypes = LeaveType::where('is_active', true)->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                // Cek apakah sudah ada saldo untuk tahun ini
                $balance = EmployeeLeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => now()->year,
                    ],
                    [
                        'total_days' => $type->default_days ?? 0,
                        'used_days' => 0,
                    ]
                );

                // Optional: reset jika sudah ada
                $balance->used_days = 0;
                $balance->total_days = $type->default_days ?? 0;
                $balance->save();
            }
        }

        $this->info('Employee leave balances reset for ' . now()->year);
    }
}
