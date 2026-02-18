<?php

namespace Database\Seeders;

use App\Models\Allowance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AllowanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = User::first();

        if (!$creator) {
            $this->command->warn('No users found. Seed users first.');
            return;
        }

        $allowances = [
            // --- Kategori Operasional (Fixed) ---
            [
                'name' => 'Transport Allowance',
                'amount' => 500000,
                'type' => 'fixed',
            ],
            [
                'name' => 'Meal Allowance',
                'amount' => 300000,
                'type' => 'fixed',
            ],
            [
                'name' => 'Internet & Communication',
                'amount' => 400000,
                'type' => 'fixed',
            ],

            // --- Kategori Jabatan & Skill (Percentage) ---
            [
                'name' => 'Position Allowance', // Tunjangan Jabatan
                'amount' => 15, // 15% dari Gaji Pokok
                'type' => 'percentage',
            ],
            [
                'name' => 'Expertise Allowance', // Tunjangan Skill/Sertifikasi
                'amount' => 10,
                'type' => 'percentage',
            ],

            // --- Kategori Performa & Kehadiran (Percentage/Fixed) ---
            [
                'name' => 'Performance Bonus',
                'amount' => 20,
                'type' => 'percentage',
            ],
            [
                'name' => 'Attendance Incentive', // Insentif Kehadiran Full
                'amount' => 250000,
                'type' => 'fixed',
            ],

            // --- Kategori Kesejahteraan (Fixed) ---
            [
                'name' => 'Health & Wellness',
                'amount' => 750000,
                'type' => 'fixed',
            ],
            [
                'name' => 'Housing Allowance',
                'amount' => 1500000,
                'type' => 'fixed',
            ],
            [
                'name' => 'Family Allowance',
                'amount' => 5, // 5% per anak/istri (logikanya nanti di payroll)
                'type' => 'percentage',
            ],
        ];

        foreach ($allowances as $data) {
            Allowance::create([
                'uuid' => Str::uuid(),
                'name' => $data['name'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'created_by_id' => $creator->id,
            ]);
        }
    }
}
