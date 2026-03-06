<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceYearSeeder extends Seeder
{
    public function run(WorkdayService $workdayService): void
    {
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

        $daysToSeed = 365;
        $totalDesiredRecords = count($employeeIds) * $daysToSeed;
        $batchSize = 500;
        $data = [];
        $count = 0;

        $this->command->getOutput()->progressStart($totalDesiredRecords);

        for ($i = 0; $i < $daysToSeed; $i++) {
            $date = Carbon::today()->subDays($i);
            $isWorkday = $workdayService->isWorkday($date);

            foreach ($employeeIds as $employeeId) {
                if (! $isWorkday) {
                    $count++;

                    continue;
                }

                // Time Simulation
                $clockIn = $date->copy()->setHour(rand(7, 9))->setMinute(rand(0, 59));
                $clockOut = $date->copy()->setHour(rand(16, 18))->setMinute(rand(0, 59));
                $status = rand(0, 10) > 1 ? 'present' : 'absent';

                $data[] = [
                    'employee_id' => $employeeId,
                    'date' => $date->format('Y-m-d'),
                    'status' => $status,
                    'clock_in' => $status === 'present' ? $clockIn : null,
                    'clock_out' => $status === 'present' ? $clockOut : null,
                    'late_minutes' => $status === 'present' ? max(0, $clockIn->diffInMinutes($date->copy()->setHour(8))) : 0,
                    'early_leave_minutes' => $status === 'present' ? max(0, $date->copy()->setHour(17)->diffInMinutes($clockOut)) : 0,
                    'work_minutes' => $status === 'present' ? $clockIn->diffInMinutes($clockOut) : 0,
                    'overtime_minutes' => $status === 'present' ? max(0, $clockOut->diffInMinutes($date->copy()->setHour(17))) : 0,
                    'latitude_in' => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_in' => 106.8 + (rand(-100, 100) / 1000),
                    'latitude_out' => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_out' => 106.8 + (rand(-100, 100) / 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $count++;
                $this->command->getOutput()->progressAdvance();

                if (count($data) >= $batchSize) {
                    Attendance::insert($data);
                    $data = [];
                }
            }
        }

        if (! empty($data)) {
            Attendance::insert($data);
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Successfully seeded attendance for the last 1 year. Owner & Director safely excluded.');
    }
}
