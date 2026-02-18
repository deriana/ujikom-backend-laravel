<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::whereHas('user.roles', function ($q) {
            $q->whereNotIn('name', [UserRole::OWNER->value, UserRole::DIRECTOR->value]);
        })->get();

        $owner = \App\Models\User::role(UserRole::OWNER->value)->first();
        $director = \App\Models\User::role(UserRole::DIRECTOR->value)->first();

        foreach ($employees as $employee) {
            // Tentukan siapa yang approve
            $approverId = $this->getApproverId($employee, $owner, $director);

            // AMBIL LEBIH BANYAK ATTENDANCE (Misal 10-15 data per orang)
            $attendances = Attendance::where('employee_id', $employee->id)
                ->latest('date') // Ambil yang paling baru
                ->take(15)
                ->get();

            foreach ($attendances as $attendance) {
                // Peluang 70% attendance ini ada lemburnya, biar gak semua hari lembur (gak logis)
                if (fake()->boolean(70)) {

                    // Status random: 80% Approved biar payroll-nya nanti ada angkanya
                    $status = fake()->randomElement([
                        ApprovalStatus::APPROVED->value,
                        ApprovalStatus::APPROVED->value, // Double biar chance lebih gede
                        ApprovalStatus::PENDING->value,
                        ApprovalStatus::REJECTED->value,
                    ]);

                    $isApproved = $status === ApprovalStatus::APPROVED->value;

                    Overtime::create([
                        'uuid' => (string) Str::uuid(),
                        'employee_id' => $employee->id,
                        'attendance_id' => $attendance->id,
                        'duration_minutes' => rand(60, 240), // 1-4 jam
                        'reason' => fake()->randomElement(['Kejar deadline fitur payroll', 'Fix bug Ujikom', 'Update dokumen HRIS']),
                        'status' => $status,
                        'approved_by_id' => $isApproved ? $approverId : null,
                        // Approved di hari yang sama dengan absen, jam 9 malam
                        'approved_at' => $isApproved ? Carbon::parse($attendance->date)->setHour(21) : null,
                        'note' => $isApproved ? 'Lanjutkan, kerja bagus.' : null,
                        // Created_at ikut tanggal absen jam 5 sore
                        'created_at' => Carbon::parse($attendance->date)->setHour(17),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    // Helper biar kode di atas rapi
    private function getApproverId($employee, $owner, $director)
    {
        if ($employee->user->hasRole(UserRole::DIRECTOR->value)) {
            return $owner?->id;
        }
        if ($employee->user->hasAnyRole([UserRole::MANAGER->value, UserRole::HR->value])) {
            return $director?->id;
        }

        return $employee->manager_id ? Employee::find($employee->manager_id)?->user_id : $director?->id;
    }
}
