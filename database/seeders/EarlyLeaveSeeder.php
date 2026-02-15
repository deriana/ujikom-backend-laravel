<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EarlyLeaveSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil employee yang punya manager
        $employees = Employee::whereNotNull('manager_id')
            ->with('manager')
            ->take(5)
            ->get();

        foreach ($employees as $employee) {

            // Ambil attendance terbaru employee
            $attendance = Attendance::where('employee_id', $employee->id)
                ->latest()
                ->first();

            if (! $attendance) {
                continue;
            }

            // Buat 3 status berbeda
            $statuses = [
                ApprovalStatus::PENDING->value,
                ApprovalStatus::APPROVED->value,
                ApprovalStatus::REJECTED->value,
            ];

            foreach ($statuses as $status) {

                EarlyLeave::create([
                    'uuid' => (string) Str::uuid(),
                    'attendance_id' => $attendance->id,
                    'employee_id' => $employee->id,
                    'minutes_early' => rand(15, 90),
                    'reason' => fake()->sentence(),
                    'attachment' => null,
                    'status' => $status,
                    'approved_by_id' => $status !== ApprovalStatus::PENDING->value
                        ? $employee->manager_id
                        : null,
                    'approved_at' => $status !== ApprovalStatus::PENDING->value
                        ? now()
                        : null,
                    'note' => $status !== ApprovalStatus::PENDING->value
                        ? fake()->sentence()
                        : null,
                ]);
            }
        }
    }
}
