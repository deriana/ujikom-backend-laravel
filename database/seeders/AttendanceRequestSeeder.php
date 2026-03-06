<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttendanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get supporting data
        $employees = Employee::whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($r) {
                $r->where('name', UserRole::OWNER->value);
            });
        })->get();

        $owner = User::role(UserRole::OWNER->value)->first();
        $director = User::role(UserRole::DIRECTOR->value)->first();

        $shiftTemplates = ShiftTemplate::all();
        $workSchedules = WorkSchedule::all();

        foreach ($employees as $employee) {
            // Determine who is entitled to approve (Hierarchy logic)
            $approverId = null;
            if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
                $approverId = $owner ? $owner->id : null;
            } elseif ($employee->user->hasAnyRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                $approverId = $director ? $director->id : null;
            } else {
                if ($employee->manager_id) {
                    $manager = Employee::find($employee->manager_id);
                    $approverId = $manager ? $manager->user_id : null;
                }
            }

            $scenarios = [
                ['status' => ApprovalStatus::PENDING->value, 'approved' => false],
                ['status' => ApprovalStatus::APPROVED->value, 'approved' => true],
                ['status' => ApprovalStatus::REJECTED->value, 'approved' => true],
            ];

            foreach ($scenarios as $scenario) {
                // Randomly determine if this is a SHIFT or WORK_MODE request
                $type = fake()->randomElement(['SHIFT', 'WORK_MODE']);

                AttendanceRequest::create([
                    'uuid' => (string) Str::uuid(),
                    'employee_id' => $employee->id,
                    'request_type' => $type,

                    // If SHIFT, fill shift_template_id. If WORK_MODE, fill work_schedules_id.
                    'shift_template_id' => $type === 'SHIFT' ? $shiftTemplates->random()->id : null,
                    'work_schedules_id' => $type === 'WORK_MODE' ? $workSchedules->random()->id : null,

                    'start_date' => now()->addDays(rand(1, 7))->format('Y-m-d'),
                    'end_date' => fake()->boolean() ? now()->addDays(rand(8, 14))->format('Y-m-d') : null,

                    'reason' => 'Request perubahan ' . strtolower($type) . ' karena ' . fake()->randomElement([
                        'keperluan proyek baru',
                        'penyesuaian jadwal kuliah',
                        'kondisi kesehatan keluarga',
                        'optimasi jam kerja tim'
                    ]),

                    'status' => $scenario['status'],
                    'approved_by_id' => $scenario['approved'] ? $approverId : null,
                    'note' => $scenario['approved'] ? 'Disetujui berdasarkan pertimbangan operasional.' : null,
                    'created_at' => now()->subDays(rand(1, 10)),
                ]);
            }
        }
    }
}
