<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EarlyLeaveSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua Employee kecuali Owner
        $employees = Employee::whereHas('user', function($q) {
            $q->whereDoesntHave('roles', function($r) {
                $r->where('name', UserRole::OWNER->value);
            });
        })->get();

        $owner = User::role(UserRole::OWNER->value)->first();
        $director = User::role(UserRole::DIRECTOR->value)->first();

        foreach ($employees as $employee) {
            // Ambil absensi terbaru (Asumsi AttendanceSeeder sudah dijalankan)
            $attendance = Attendance::where('employee_id', $employee->id)
                ->latest()
                ->first();

            if (!$attendance) continue;

            // Tentukan siapa pemberi izin (Approver) berdasarkan hirarki kita
            $approverId = null;

            if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
                $approverId = $owner ? $owner->id : null;
            } elseif ($employee->user->hasRole([UserRole::MANAGER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
                $approverId = $director ? $director->id : null;
            } else {
                // Staff biasa lapor ke Manager-nya
                if ($employee->manager_id) {
                    $manager = Employee::find($employee->manager_id);
                    $approverId = $manager ? $manager->user_id : null;
                }
            }

            // Simulasi 3 kondisi: Izin yang masih nunggu, di-acc, dan ditolak
            $scenarios = [
                ['status' => ApprovalStatus::PENDING->value, 'approved' => false],
                ['status' => ApprovalStatus::APPROVED->value, 'approved' => true],
                ['status' => ApprovalStatus::REJECTED->value, 'approved' => true],
            ];

            foreach ($scenarios as $scenario) {
                EarlyLeave::create([
                    'uuid' => (string) Str::uuid(),
                    'attendance_id' => $attendance->id,
                    'employee_id' => $employee->id,
                    'minutes_early' => rand(30, 120), // Pulang lebih awal 0.5 - 2 jam
                    'reason' => "Izin pulang cepat karena " . fake()->randomElement(['keperluan keluarga', 'sakit tiba-tiba', 'urusan mendesak']),
                    'attachment' => null,
                    'status' => $scenario['status'],
                    'approved_by_id' => $scenario['approved'] ? $approverId : null,
                    'approved_at' => $scenario['approved'] ? now() : null,
                    'note' => $scenario['approved'] ? "Diberikan izin oleh atasan." : null,
                ]);
            }
        }
    }
}
