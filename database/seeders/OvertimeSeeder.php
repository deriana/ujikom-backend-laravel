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
            // Determine who will approve
            $approverId = $this->getApproverId($employee, $owner, $director);

            // FETCH MORE ATTENDANCE (e.g., 10-15 records per person)
            $attendances = Attendance::where('employee_id', $employee->id)
                ->latest('date') // Get the most recent ones
                ->take(15)
                ->get();

            foreach ($attendances as $attendance) {
                // 70% chance this attendance has overtime, so not every day is overtime (unrealistic)
                if (fake()->boolean(70)) {

                    // Random status: Higher chance for Approved so payroll data is populated
                    $status = fake()->randomElement([
                        ApprovalStatus::APPROVED->value,
                        ApprovalStatus::APPROVED->value, // Double to increase chance
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
                        // Approved on the same day as attendance, at 9 PM
                        'approved_at' => $isApproved ? Carbon::parse($attendance->date)->setHour(21) : null,
                        'note' => $isApproved ? 'Lanjutkan, kerja bagus.' : null,
                        // Created_at follows attendance date at 5 PM
                        'created_at' => Carbon::parse($attendance->date)->setHour(17),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    // Helper to keep the code clean
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
