<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua employee
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->info('No employees found, skipping attendance seeder.');

            return;
        }

        foreach ($employees as $employee) {
            // buat 10 hari data absensi terakhir
            for ($i = 0; $i < 50; $i++) {
                $date = Carbon::today()->subDays($i);

                // acak jam masuk/keluar
                $clockIn = $date->copy()->setHour(rand(7, 9))->setMinute(rand(0, 59));
                $clockOut = $date->copy()->setHour(rand(16, 18))->setMinute(rand(0, 59));

                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => rand(0, 1) ? 'present' : 'absent',
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late_minutes' => max(0, $clockIn->diffInMinutes($date->copy()->setHour(8))),
                    'early_leave_minutes' => max(0, $date->copy()->setHour(17)->diffInMinutes($clockOut)),
                    'work_minutes' => $clockIn->diffInMinutes($clockOut),
                    'overtime_minutes' => max(0, $clockOut->diffInMinutes($date->copy()->setHour(17))),
                    'clock_in_photo' => null, // bisa diisi file placeholder
                    'clock_out_photo' => null,
                    'latitude_in' => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_in' => 106.8 + (rand(-100, 100) / 1000),
                    'latitude_out' => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_out' => 106.8 + (rand(-100, 100) / 1000),
                ]);
            }
        }
    }
}
