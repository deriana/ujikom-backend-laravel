<?php

namespace Database\Seeders;

use App\Enums\PointCategoryEnum;
use App\Models\Employee;
use App\Models\PointPeriode;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\PointWallet; // Gunakan Enum Kategori yang baru
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PointSystemSeeder extends Seeder
{
    public function run(): void
    {
        $VALUE_ON_TIME = 0;
        $VALUE_ABSENT = 480;

        // 1. Buat Aturan Poin Dinamis (Master Data)
        // Kita pecah Late menjadi beberapa kondisi agar lebih adil
        $rules = [
            // KATEGORI: ATTENDANCE (Kehadiran)
            [
                'category' => PointCategoryEnum::ATTENDANCE,
                'event_name' => 'Hadir Tepat Waktu / Dalam Toleransi',
                'points' => 2,
                'operator' => '==',
                'min_value' => $VALUE_ON_TIME, // 0 menit dari hasil kalkulasi net_late
                'system_reserve' => true,
                'description' => 'Bonus hadir tepat waktu atau masih dalam batas toleransi shift.',
            ],
            [
                'category' => PointCategoryEnum::ATTENDANCE,
                'event_name' => 'Telat Ringan (1-15 Menit)',
                'points' => -2,
                'operator' => 'BETWEEN',
                'min_value' => 1,
                'max_value' => 15,
                'system_reserve' => true,
                'description' => 'Potongan poin untuk keterlambatan tipis.',
            ],
            [
                'category' => PointCategoryEnum::ATTENDANCE,
                'event_name' => 'Telat Parah (> 30 Menit)',
                'points' => -10,
                'operator' => '>',
                'min_value' => 30,
                'system_reserve' => true,
                'description' => 'Potongan besar untuk keterlambatan parah di atas 30 menit.',
            ],
            [
                'category' => PointCategoryEnum::ATTENDANCE,
                'event_name' => 'Mangkir (Alpha)',
                'points' => -20,
                'operator' => '==',
                'min_value' => $VALUE_ABSENT, // Contoh: dianggap mangkir jika telat 8 jam (480 mnt)
                'system_reserve' => true,
                'description' => 'Pelanggaran berat tidak hadir tanpa keterangan.',
            ],

            // KATEGORI: PERFORMANCE & OTHERS
            [
                'category' => PointCategoryEnum::ATTENDANCE,
                'event_name' => 'Lembur (Minimal 1 Jam)',
                'points' => 5,
                'operator' => '>=',
                'min_value' => 60,
                'description' => 'Bonus poin per jam lembur.',
            ],
            [
                'category' => PointCategoryEnum::ACHIEVEMENT,
                'event_name' => 'Bonus Manager',
                'points' => 20,
                'operator' => '==',
                'min_value' => 1, // Trigger manual
                'description' => 'Apresiasi khusus dari atasan langsung.',
            ],
        ];

        foreach ($rules as $rule) {
            PointRule::updateOrCreate(
                ['event_name' => $rule['event_name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'category' => $rule['category'],
                    'points' => $rule['points'],
                    'operator' => $rule['operator'],
                    'min_value' => $rule['min_value'] ?? null,
                    'max_value' => $rule['max_value'] ?? null,
                    'system_reserve' => $rule['system_reserve'] ?? false,
                    'description' => $rule['description'],
                    'is_active' => true,
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

        // 3. Simulasi Transaksi Poin
        $employees = Employee::limit(5)->get();

        // Ambil Rule secara dinamis untuk simulasi
        $ruleHadir = PointRule::where('event_name', 'Hadir Tepat Waktu / Dalam Toleransi')->first();
        $ruleTelat = PointRule::where('event_name', 'Telat Parah (> 30 Menit)')->first();
        $ruleBonus = PointRule::where('event_name', 'Bonus Manager')->first();

        foreach ($employees as $index => $employee) {
            // Inisialisasi Wallet untuk setiap employee di periode ini
            PointWallet::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'point_period_id' => $period->id,
                ],
            );

            // Simulasi hadir tepat waktu 5 kali
            for ($i = 0; $i < 5; $i++) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleHadir->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleHadir->points,
                ]);
            }

            // Employee pertama dapet bonus
            if ($index === 0) {
                PointTransaction::create([
                    'uuid' => Str::uuid(),
                    'employee_id' => $employee->id,
                    'point_rule_id' => $ruleBonus->id,
                    'point_period_id' => $period->id,
                    'current_points' => $ruleBonus->points,
                ]);
            }

            // Employee terakhir sering telat parah
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

        // Tambahkan di baris paling bawah Seeder setelah loop selesai
        $wallets = PointWallet::all();
        foreach ($wallets as $wallet) {
            $total = PointTransaction::where('employee_id', $wallet->employee_id)
                ->where('point_period_id', $wallet->point_period_id)
                ->sum('current_points');

            $wallet->update(['current_balance' => $total]);
        }

        $this->command->info('Point system seeding completed successfully!');
    }
}
