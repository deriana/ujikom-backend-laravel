<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmployeeLeaveSeeder extends Seeder
{
    /**
     * Run the EmployeeLeave seeder based on approved Leaves.
     */
    public function run(): void
    {
        // 1. Get all PENDING leaves and randomly set them to APPROVED
        // so that data is populated in the EmployeeLeave table
        $pendingLeaves = Leave::where('approval_status', ApprovalStatus::PENDING->value)->get();
        foreach ($pendingLeaves as $pending) {
            if (rand(0, 1)) { // 50% chance to be approved
                $pending->update(['approval_status' => ApprovalStatus::APPROVED->value]);
            }
        }

        // 2. Fetch data that is already APPROVED
        $leaves = Leave::with(['leaveType', 'employee'])
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->get();

        foreach ($leaves as $leave) {
            // Day calculation logic
            $days = $leave->is_half_day ? 0.5 : $leave->date_start->diffInDays($leave->date_end) + 1;

            // Create Record in EmployeeLeave (Final data for Payroll)
            EmployeeLeave::updateOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start->toDateString(),
                    'end_date' => $leave->date_end->toDateString(),
                ],
                [
                    'uuid' => Str::uuid(),
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                    'created_by_id' => $leave->employee->user_id, // Assumption: created by the related user
                ]
            );

            // 3. Update EmployeeLeaveBalance
            $balance = EmployeeLeaveBalance::firstOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year' => $leave->date_start->year,
                ],
                [
                    'total_days' => $leave->leaveType->default_days ?? 0,
                    'used_days' => 0,
                ]
            );

            // Call useDays method from model to reduce balance
            // Ensure the useDays() method exists in the EmployeeLeaveBalance Model
            $balance->useDays($days);
        }
    }
}
