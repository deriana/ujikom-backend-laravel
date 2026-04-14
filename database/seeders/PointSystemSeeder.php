<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Enums\PointRuleEnum; // Import Enum
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PointSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Aturan Poin (Master Data) menggunakan Enum
        $rules = [
            [
                'event_name' => PointRuleEnum::PRESENT->value,
                'points' => 2,
                'description' => 'Diberikan jika melakukan absensi sebelum jam masuk.',
                'is_active' => true,
            ],
            [
                'event_name' => PointRuleEnum::LATE->value,
                'points' => -2,
                'description' => 'Pengurangan poin jika absen melewati batas toleransi.',
                'is_active' => true,
            ],
            [
                'event_name' => PointRuleEnum::ABSENT->value,
                'points' => -10,
                'description' => 'Pelanggaran berat karena tidak hadir tanpa keterangan.',
                'is_active' => true,
            ],
            [
                'event_name' => PointRuleEnum::OVERTIME->value,
                'points' => 5,
                'description' => 'Bonus poin jika bekerja melebihi jam kerja resmi (minimal 1 jam).',
                'is_active' => true,
            ],
            [
                'event_name' => PointRuleEnum::EARLY_LEAVE->value,
                'points' => -5,
                'description' => 'Pengurangan poin jika melakukan clock-out sebelum waktunya tanpa izin.',
                'is_active' => true,
            ],
            // Tambahan diluar Enum jika masih dibutuhkan
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
            PointRule::updateOrCreate(
                ['event_name' => $rule['event_name']], // Hindari duplikat jika seeder dijalankan ulang
                [
                    'uuid' => (string) Str::uuid(),
                    'points' => $rule['points'],
                    'description' => $rule['description'],
                    'is_active' => $rule['is_active'],
                ]
            );
        }

        // 2. Buat Periode Aktif
        $period = PointPeriode::updateOrCreate(
            ['name' => 'April 2026'],
            [
                'uuid' => Str::uuid(),
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
                'is_active' => true,
            ]
        );

        // 3. Buat Simulasi Transaksi Poin
        $employees = Employee::limit(5)->get();

        // Ambil ID Rule dari database
        $ruleHadir = PointRule::where('event_name', PointRuleEnum::PRESENT->value)->first();
        $ruleBonus = PointRule::where('event_name', 'Bonus Manager')->first();
        $ruleTelat = PointRule::where('event_name', PointRuleEnum::LATE->value)->first();
        $ruleLembur = PointRule::where('event_name', PointRuleEnum::OVERTIME->value)->first();

        foreach ($employees as $index => $employee) {
            // Simulasi: Semua hadir 5 kali
            for ($i = 0; $i < 5; $i++) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleHadir->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleHadir->points,
                ]);
            }

            // Employee pertama dapet bonus dan lembur (Juara Leaderboard)
            if ($index === 0) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleBonus->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleBonus->points,
                ]);

                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleLembur->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleLembur->points,
                ]);
            }

            // Employee terakhir sering telat
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
