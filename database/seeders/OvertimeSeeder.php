<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Overtime;
use App\Models\Employee;
use App\Models\Attendance;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;

class OvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua employee kecuali OWNER
        $employees = Employee::whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($r) {
                $r->where('name', UserRole::OWNER->value);
            });
        })->get();

        // Ambil user untuk hierarchy approval
        $owner = \App\Models\User::role(UserRole::OWNER->value)->first();
        $director = \App\Models\User::role(UserRole::DIRECTOR->value)->first();

        foreach ($employees as $employee) {
            // Tentukan approver berdasarkan role/hierarchy
            $approverId = null;
            if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
                $approverId = $owner ? $owner->id : null;
            } elseif ($employee->user->hasAnyRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                $approverId = $director ? $director->id : null;
            } elseif ($employee->manager_id) {
                $manager = Employee::find($employee->manager_id);
                $approverId = $manager ? $manager->user_id : null;
            }

            // Ambil beberapa attendance random untuk employee
            $attendances = Attendance::where('employee_id', $employee->id)
                ->inRandomOrder()
                ->take(3)
                ->get();

            foreach ($attendances as $attendance) {
                $scenarios = [
                    ['status' => ApprovalStatus::PENDING->value, 'approved' => false],
                    ['status' => ApprovalStatus::APPROVED->value, 'approved' => true],
                    ['status' => ApprovalStatus::REJECTED->value, 'approved' => true],
                ];

                foreach ($scenarios as $scenario) {
                    Overtime::create([
                        'uuid' => (string) Str::uuid(),
                        'employee_id' => $employee->id,
                        'attendance_id' => $attendance->id,
                        'duration_minutes' => rand(30, 180), // durasi random 30-180 menit
                        'reason' => fake()->sentence(6),
                        'status' => $scenario['status'],
                        'approved_by_id' => $scenario['approved'] ? $approverId : null,
                        'approved_at' => $scenario['approved'] ? now()->subDays(rand(0, 5)) : null,
                        'note' => $scenario['approved'] ? fake()->sentence(10) : null,
                        'created_at' => now()->subDays(rand(1, 10)),
                        'updated_at' => now()->subDays(rand(0, 5)),
                    ]);
                }
            }
        }
    }
}
