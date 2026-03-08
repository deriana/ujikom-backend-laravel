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
            '--month' => 11,
            '--year' => 2025,
        ]);

        $this->command->info('Payroll data has been generated successfully!');
    }
}
