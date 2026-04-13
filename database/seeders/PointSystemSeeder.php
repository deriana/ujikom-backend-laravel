<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PointSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Aturan Poin (Master Data)
        $rules = [
            [
                'event_name' => 'Hadir Tepat Waktu',
                'points' => 2,
                'description' => 'Diberikan jika melakukan absensi sebelum jam masuk.',
                'is_active' => true,
            ],
            [
                'event_name' => 'Terlambat',
                'points' => -2,
                'description' => 'Pengurangan poin jika absen melewati batas toleransi.',
                'is_active' => true,
            ],
            [
                'event_name' => 'Mangkir (Alpha)',
                'points' => -10,
                'description' => 'Pelanggaran berat karena tidak hadir tanpa keterangan.',
                'is_active' => true,
            ],
            [
                'event_name' => 'Penyelesaian Tugas Cepat',
                'points' => 5,
                'description' => 'Bonus poin jika tugas selesai sebelum deadline.',
                'is_active' => true,
            ],
            [
                'event_name' => 'Bonus Manager',
                'points' => 20,
                'description' => 'Apresiasi khusus dari atasan langsung.',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            PointRule::create([
                'uuid' => (string) Str::uuid(),
                'event_name' => $rule['event_name'],
                'points' => $rule['points'],
                'description' => $rule['description'],
                'is_active' => $rule['is_active'],
            ]);
        }

        // 2. Buat Periode Aktif
        $period = PointPeriode::create([
            'uuid' => Str::uuid(),
            'name' => 'April 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'is_active' => true,
        ]);

        // 3. Buat Simulasi Transaksi Poin (Leaderboard Test)
        // Ambil beberapa employee yang sudah ada
        $employees = Employee::limit(5)->get();
        $ruleHadir = PointRule::where('event_name', 'Hadir Tepat Waktu')->first();
        $ruleBonus = PointRule::where('event_name', 'Bonus Manager')->first();
        $ruleTelat = PointRule::where('event_name', 'Terlambat')->first();

        foreach ($employees as $index => $employee) {
            // Kasih poin hadir 5 kali buat semua
            for ($i = 0; $i < 5; $i++) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleHadir->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleHadir->points,
                ]);
            }

            // Kasih bonus gede buat employee pertama (biar jadi juara 1 di leaderboard)
            if ($index === 0) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleBonus->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleBonus->points,
                ]);
            }

            // Kasih telat buat employee terakhir (biar poinnya rendah)
            if ($index === 4) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleTelat->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleTelat->points,
                ]);
            }
        }
    }
}
