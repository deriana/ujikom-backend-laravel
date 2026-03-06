<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceNowSeeder extends Seeder
{
    public function run(): void
    {
        // Get Employees except Owner & Director
        $employeeIds = Employee::whereHas('user', function ($q) {
            $q->withoutRole([
                UserRole::OWNER->value,
                UserRole::DIRECTOR->value,
            ]);
        })->pluck('id')->toArray();

        if (empty($employeeIds)) {
            $this->command->info('No valid staff found (Owner & Director skipped), skipping seeder.');

            return;
        }

        $today = Carbon::today();

        // If today is weekend, do not generate
        if ($today->isWeekend()) {
            $this->command->info('Today is weekend. No attendance generated.');

            return;
        }

        $data = [];

        foreach ($employeeIds as $employeeId) {

            $clockIn = $today->copy()->setHour(rand(7, 9))->setMinute(rand(0, 59));
            $clockOut = $today->copy()->setHour(rand(16, 18))->setMinute(rand(0, 59));
            $status = rand(0, 10) > 1 ? 'present' : 'absent';

            $data[] = [
                'employee_id' => $employeeId,
                'date' => $today->format('Y-m-d'),
                'status' => $status,
                'clock_in' => $status === 'present' ? $clockIn : null,
                'clock_out' => $status === 'present' ? $clockOut : null,
                'late_minutes' => $status === 'present' ? max(0, $clockIn->diffInMinutes($today->copy()->setHour(8))) : 0,
                'early_leave_minutes' => $status === 'present' ? max(0, $today->copy()->setHour(17)->diffInMinutes($clockOut)) : 0,
                'work_minutes' => $status === 'present' ? $clockIn->diffInMinutes($clockOut) : 0,
                'overtime_minutes' => $status === 'present' ? max(0, $clockOut->diffInMinutes($today->copy()->setHour(17))) : 0,
                'latitude_in' => -6.2 + (rand(-100, 100) / 1000),
                'longitude_in' => 106.8 + (rand(-100, 100) / 1000),
                'latitude_out' => -6.2 + (rand(-100, 100) / 1000),
                'longitude_out' => 106.8 + (rand(-100, 100) / 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Attendance::insert($data);

        $this->command->info('Successfully seeded attendance for today only.');
    }
}
