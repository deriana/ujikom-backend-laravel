<?php

namespace App\Console\Commands;

use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyPayroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        payroll:generate
        {--payday=26 : Tanggal gajian}
        {--month= : Bulan payroll (1-12)}
        {--year= : Tahun payroll}
    '; /**< Nama dan signature command di terminal dengan opsi payday, month, dan year */

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly payroll draft for active employees'; /**< Deskripsi singkat fungsi command */

    /**
     * Menjalankan logika command untuk membuat draf payroll bulanan bagi karyawan aktif.
     *
     * @return int Status keluar (0 untuk sukses, 1 untuk gagal)
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $month = $this->option('month') ?? ($today->day < 26 ? $today->copy()->subMonth()->month : $today->month);
        $year = $this->option('year') ?? ($today->day < 26 && ! $this->option('month') ? $today->copy()->subMonth()->year : $today->year);

        $periodStart = Carbon::create($year, $month)->startOfMonth();
        $periodEnd = Carbon::create($year, $month)->endOfMonth();

        $this->info("Generating payroll for period {$periodStart->toDateString()} - {$periodEnd->toDateString()}");

        try {
            $payrollService = app(PayrollService::class);
            $payrolls = $payrollService->generateMonthlyPayroll($periodStart, $periodEnd, 1);

            $this->info('Payroll draft generated successfully for ' . $payrolls->count() . ' employees.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
