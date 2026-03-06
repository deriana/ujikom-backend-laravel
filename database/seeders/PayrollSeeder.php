<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('payroll:generate', [
            '--payday' => 26,
            '--month' => now()->month,
            '--year' => now()->year,
        ]);

        $this->command->info('Payroll data has been generated successfully!');
    }
}
