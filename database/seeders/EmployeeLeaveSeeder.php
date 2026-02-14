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
     * Jalankan seeder EmployeeLeave berdasarkan Leave yang sudah approved
     */
    public function run(): void
    {
        $leaves = Leave::with('leaveType', 'employee')->where('approval_status', ApprovalStatus::APPROVED->value)->get();

        foreach ($leaves as $leave) {
            $days = $leave->is_half_day ? 0.5 : $leave->date_start->diffInDays($leave->date_end) + 1;

            // Buat EmployeeLeave
            EmployeeLeave::updateOrCreate(
                [
                    'uuid' => Str::uuid(),
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start,
                    'end_date' => $leave->date_end,
                ],
                [
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                    'created_by_id' => $leave->created_by_id,
                    'updated_by_id' => $leave->updated_by_id,
                ]
            );

            // Update saldo EmployeeLeaveBalance
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

            $balance->useDays($days);
        }
    }
}
