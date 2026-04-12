<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AttendanceCorrectionSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        // Ambil beberapa data contoh untuk relasi
        $employees = Employee::whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($r) {
                $r->whereIn('name', [UserRole::OWNER->value, UserRole::DIRECTOR->value]);
            });
        })->limit(5)->get();

        $hrAdmin = Employee::whereHas('user', function ($q) {
            $q->role(UserRole::HR->value);
        })->first() ?? Employee::first();

        foreach ($employees as $employee) {
            // Cari data presensi milik karyawan ini untuk dikoreksi
            $attendance = Attendance::where('employee_id', $employee->id)->first();

            if ($attendance) {
                // 1. Contoh Data Pending
                AttendanceCorrection::create([
                    'uuid' => Str::uuid(),
                    'attendance_id'       => $attendance->id,
                    'employee_id'         => $employee->id,
                    'clock_in_requested'  => Carbon::now()->setHour(8)->setMinute(0),
                    'clock_out_requested' => Carbon::now()->setHour(17)->setMinute(0),
                    'reason'              => 'Lupa melakukan presensi masuk karena terburu-buru rapat.',
                    'attachment'          => 'corrections/evidence_1.jpg',
                    'status'              => 0, // Pending
                ]);

                // 2. Contoh Data Approved
                AttendanceCorrection::create([
                    'uuid' => Str::uuid(),
                    'attendance_id'       => $attendance->id,
                    'employee_id'         => $employee->id,
                    'clock_in_requested'  => Carbon::now()->subDay()->setHour(8)->setMinute(15),
                    'clock_out_requested' => Carbon::now()->subDay()->setHour(17)->setMinute(30),
                    'reason'              => 'Sistem error saat mencoba clock out kemarin sore.',
                    'attachment'          => null,
                    'status'              => 1, // Approved
                    'approver_id'         => $hrAdmin->id,
                    'approved_at'         => Carbon::now(),
                    'note'                => 'Disetujui berdasarkan log sistem.',
                ]);
            }
        }
    }
}
