<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate payroll for the last 3 months including current month
        for ($i = 2; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            // If it's the current month, only generate if today is at least the 26th
            if ($i === 0 && Carbon::now()->day < 26) {
                continue;
            }

            Artisan::call('payroll:generate', [
                '--payday' => 26,
                '--month' => $date->month,
                '--year' => $date->year,
            ]);
        }

        $this->command->info('Payroll data has been generated successfully!');
    }
}
