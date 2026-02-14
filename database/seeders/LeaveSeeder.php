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

        if ($employees->isEmpty() || $leaveTypes->isEmpty()) {
            $this->command->warn('Seeder Leave gagal: pastikan ada data Employee dan LeaveType');

            return;
        }

        foreach ($employees as $employee) {
            $leaveCount = rand(1, 3);

            for ($i = 0; $i < $leaveCount; $i++) {
                $type = $leaveTypes->random();

                $startDate = Carbon::now()->subDays(rand(0, 60));
                $endDate = (clone $startDate)->addDays(rand(0, 5));

                // Buat leave
                $leave = Leave::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'date_start' => $startDate,
                    'date_end' => $endDate,
                    'reason' => "Cuti {$type->display_name} untuk testing",
                    'attachment' => null,
                    'approval_status' => ApprovalStatus::PENDING->value,
                    'is_half_day' => rand(0, 1) ? true : false,
                ]);

                // Buat approval manager
                if ($employee->manager_id) {
                    LeaveApproval::create([
                        'uuid' => Str::uuid(),
                        'leave_id' => $leave->id,
                        'approver_id' => $employee->manager_id,
                        'level' => 0, // manager
                        'status' => ApprovalStatus::PENDING->value,
                    ]);
                }

                // Buat approval HR (level 1) untuk testing, nanti bisa approve setelah manager
                $hr = User::role(UserRole::HR->value)->first();
                if ($hr) {
                    LeaveApproval::create([
                        'uuid' => Str::uuid(),
                        'leave_id' => $leave->id,
                        'approver_id' => $hr->id,
                        'level' => 1, // HR
                        'status' => ApprovalStatus::PENDING->value,
                    ]);
                }
            }
        }
    }
}
