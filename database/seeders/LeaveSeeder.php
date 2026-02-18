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
            $this->command->warn('Seeder Leave gagal: pastikan ada data Employee dan LeaveType');
            return;
        }

        foreach ($employees as $employee) {
            // Owner tidak perlu cuti
            if ($employee->user->hasRole(UserRole::OWNER->value)) {
                continue;
            }

            $leaveCount = rand(1, 2);

            for ($i = 0; $i < $leaveCount; $i++) {
                // Filter jenis cuti berdasarkan gender employee
                $suitableTypes = $leaveTypes->filter(function ($type) use ($employee) {
                    return $type->gender === 'all' || $type->gender === $employee->gender;
                });

                if ($suitableTypes->isEmpty()) continue;

                $type = $suitableTypes->random();
                $startDate = Carbon::now()->subDays(rand(1, 30));
                $endDate = (clone $startDate)->addDays(rand(0, 3));
                $status = ApprovalStatus::PENDING; // Default pending untuk simulasi

                $leave = Leave::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'date_start' => $startDate,
                    'date_end' => $endDate,
                    'reason' => "Pengajuan cuti {$type->name} - Testing Hirarki",
                    'approval_status' => $status->value,
                    'is_half_day' => rand(0, 1) ? true : false,
                ]);

                /*
                |--------------------------------------------------------------------------
                | LOGIKA HIRARKI APPROVAL
                |--------------------------------------------------------------------------
                */

                // 1. Jika yang cuti adalah Director, yang approve adalah OWNER
                if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
                    if ($owner) {
                        $this->createApproval($leave->id, $owner->id, 0);
                    }
                }

                // 2. Jika yang cuti adalah Manager, HR, atau Finance, yang approve adalah DIRECTOR
                elseif ($employee->user->hasRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                    if ($director) {
                        $this->createApproval($leave->id, $director->id, 0);
                    }
                }

                // 3. Jika Staff biasa, approve oleh Managernya, lalu HR
                else {
                    // Level 0: Manager Langsung
                    if ($employee->manager_id) {
                        // Ambil user_id dari manager si employee
                        $managerUser = Employee::find($employee->manager_id)->user;
                        $this->createApproval($leave->id, $managerUser->id, 0);
                    }

                    // Level 1: HR Departemen
                    $hr = User::role(UserRole::HR->value)->first();
                    if ($hr) {
                        $this->createApproval($leave->id, $hr->id, 1);
                    }
                }
            }
        }
    }

    /**
     * Helper untuk membuat data approval
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
