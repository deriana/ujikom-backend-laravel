<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $leaveTypes = LeaveType::all();
        $owner = User::role(UserRole::OWNER->value)->first();
        $director = User::role(UserRole::DIRECTOR->value)->first();

        if ($employees->isEmpty() || $leaveTypes->isEmpty()) {
            $this->command->warn('Leave Seeder failed: ensure Employee and LeaveType data exists');
            return;
        }

        foreach ($employees as $employee) {
            // Owner does not need leave
            if ($employee->user->hasRole(UserRole::OWNER->value)) {
                continue;
            }

            $leaveCount = rand(1, 2);

            for ($i = 0; $i < $leaveCount; $i++) {
                // Filter leave types based on employee gender
                $suitableTypes = $leaveTypes->filter(function ($type) use ($employee) {
                    return $type->gender === 'all' || $type->gender === $employee->gender;
                });

                if ($suitableTypes->isEmpty()) continue;

                $type = $suitableTypes->random();

                // Set range to current month
                $startDate = Carbon::now()->startOfMonth()->addDays(rand(0, Carbon::now()->daysInMonth - 5));
                $endDate = (clone $startDate)->addDays(rand(0, 2));

                $status = ApprovalStatus::PENDING; // Default pending for simulation

                $leave = Leave::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'date_start' => $startDate,
                    'date_end' => $endDate,
                    'reason' => "Leave request {$type->name} - Hierarchy Testing",
                    'approval_status' => $status->value,
                    'is_half_day' => rand(0, 1) ? true : false,
                ]);

                /*
                |--------------------------------------------------------------------------
                | APPROVAL HIERARCHY LOGIC
                |--------------------------------------------------------------------------
                */

                // 1. If the one taking leave is Director, the approver is OWNER
                if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
                    if ($owner) {
                        $this->createApproval($leave->id, $owner->id, 0);
                    }
                }

                // 2. If the one taking leave is Manager, HR, or Finance, the approver is DIRECTOR
                elseif ($employee->user->hasRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                    if ($director) {
                        $this->createApproval($leave->id, $director->id, 0);
                    }
                }

                // 3. If regular Staff, approved by their Manager, then HR
                else {
                    // Level 0: Direct Manager
                    if ($employee->manager_id) {
                        // Get user_id from the employee's manager
                        $managerUser = Employee::find($employee->manager_id)->user;
                        $this->createApproval($leave->id, $managerUser->id, 0);
                    }

                    // Level 1: HR Department
                    $hr = User::role(UserRole::HR->value)->first();
                    if ($hr) {
                        $this->createApproval($leave->id, $hr->id, 1);
                    }
                }
            }
        }
    }

    /**
     * Helper to create approval data
     */
    private function createApproval($leaveId, $approverId, $level)
    {
        LeaveApproval::create([
            'uuid' => Str::uuid(),
            'leave_id' => $leaveId,
            'approver_id' => $approverId,
            'level' => $level,
            'status' => ApprovalStatus::PENDING->value,
        ]);
    }
}
