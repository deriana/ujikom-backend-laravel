<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua employee dalam chunk agar hemat memori
        $employeeIds = Employee::pluck('id')->toArray();

        if (empty($employeeIds)) {
            $this->command->info('No employees found, skipping attendance seeder.');
            return;
        }

        $totalDesiredRecords = 100000;
        $batchSize = 500; // Masukkan 500 data per satu kali query SQL
        $data = [];
        $count = 0;

        $this->command->getOutput()->progressStart($totalDesiredRecords);

        while ($count < $totalDesiredRecords) {
            foreach ($employeeIds as $employeeId) {
                if ($count >= $totalDesiredRecords) break;

                // Hitung mundur hari berdasarkan urutan count agar tanggal bervariasi
                $daysBack = floor($count / count($employeeIds));
                $date = Carbon::today()->subDays($daysBack);

                // Lewati hari Sabtu & Minggu (Opsional, agar data lebih real)
                if ($date->isWeekend()) {
                    $count++; // Tetap hitung agar loop tidak stuck
                    continue;
                }

                $clockIn = $date->copy()->setHour(rand(7, 9))->setMinute(rand(0, 59));
                $clockOut = $date->copy()->setHour(rand(16, 18))->setMinute(rand(0, 59));

                $data[] = [
                    'employee_id'         => $employeeId,
                    'date'                => $date->format('Y-m-d'),
                    'status'              => rand(0, 10) > 1 ? 'present' : 'absent', // 90% hadir
                    'clock_in'            => $clockIn,
                    'clock_out'           => $clockOut,
                    'late_minutes'        => max(0, $clockIn->diffInMinutes($date->copy()->setHour(8))),
                    'early_leave_minutes' => max(0, $date->copy()->setHour(17)->diffInMinutes($clockOut)),
                    'work_minutes'        => $clockIn->diffInMinutes($clockOut),
                    'overtime_minutes'    => max(0, $clockOut->diffInMinutes($date->copy()->setHour(17))),
                    'latitude_in'         => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_in'        => 106.8 + (rand(-100, 100) / 1000),
                    'latitude_out'        => -6.2 + (rand(-100, 100) / 1000),
                    'longitude_out'       => 106.8 + (rand(-100, 100) / 1000),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                $count++;
                $this->command->getOutput()->progressAdvance();

                // Jika sudah mencapai batch size, masukkan ke database dan kosongkan array
                if (count($data) >= $batchSize) {
                    DB::table('attendances')->insert($data);
                    $data = [];
                }
            }
        }

        // Masukkan sisa data jika ada
        if (!empty($data)) {
            DB::table('attendances')->insert($data);
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("Successfully seeded $count attendance records.");
    }
}
