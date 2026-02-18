<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyPayroll extends Command
{
    protected $signature = '
        payroll:generate
        {--payday=26 : Tanggal gajian}
        {--month= : Bulan payroll (1-12)}
        {--year= : Tahun payroll}
    ';

    protected $description = 'Generate monthly payroll draft';

    public function handle(): int
    {
        $today = Carbon::today();
        $month = $this->option('month') ?? ($today->day < 26 ? $today->copy()->subMonth()->month : $today->month);
        $year = $this->option('year') ?? ($today->day < 26 && ! $this->option('month') ? $today->copy()->subMonth()->year : $today->year);

        $periodStart = Carbon::create($year, $month)->startOfMonth();
        $periodEnd = Carbon::create($year, $month)->endOfMonth();

        $this->info("Generating payroll for period {$periodStart->toDateString()} - {$periodEnd->toDateString()}");

        // Mengambil data dengan Eager Loading dan Constraints untuk efisiensi memori (Anti N+1)
        $employees = Employee::whereHas('user.roles', function ($query) {
            $query->where('name', '!=', UserRole::OWNER);
        })
            ->with(['position.allowances', 'user'])
            ->with(['attendances' => function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('date', [$periodStart, $periodEnd]);
            }])
            ->with(['overtimes' => function ($q) use ($periodStart, $periodEnd) {
                $q->approved()
                    ->whereHas('attendance', function ($query) use ($periodStart, $periodEnd) {
                        $query->whereBetween('date', [$periodStart, $periodEnd]);
                    });
            }])
            ->get();

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('period_start', $periodStart->toDateString())
                    ->where('period_end', $periodEnd->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                $baseSalary = (float) ($employee->base_salary ?? 0);
                $hourlyRate = $baseSalary > 0 ? ($baseSalary / 173) : 0;

                // --- 1. ALLOWANCE ---
                $allowanceTotal = 0;
                if ($employee->position) {
                    foreach ($employee->position->allowances as $allowance) {
                        $amount = $allowance->pivot?->amount ?? $allowance->amount;
                        $allowanceTotal += ($allowance->type === 'percentage')
                            ? $baseSalary * ($amount / 100)
                            : (float) $amount;
                    }
                }

                // --- 2. OVERTIME ---
                // Catatan 2: Mengambil dari collection yang sudah di-load di awal (lebih cepat)
                $overtimeMinutes = $employee->overtimes->sum('duration_minutes');
                $overtimePay = ($overtimeMinutes / 60) * $hourlyRate;

                $grossSalary = $baseSalary + $allowanceTotal + $overtimePay;

                // --- 3. ATTENDANCE DEDUCTION ---
                $lateMinutes = $employee->attendances->sum('late_minutes');
                $earlyLeaveMinutes = $employee->attendances
                    ->where('is_early_leave_approved', false)
                    ->sum('early_leave_minutes');

                $lateDeduction = ($lateMinutes / 60) * $hourlyRate;
                $earlyLeaveDeduction = ($earlyLeaveMinutes / 60) * $hourlyRate;
                $attendanceDeduction = $lateDeduction + $earlyLeaveDeduction;

                // --- 4. TAX CALCULATION ---
                $taxableIncome = $grossSalary - $attendanceDeduction;
                $ptkp = 5000000;
                $taxRate = 0.05;

                $taxAmount = $taxableIncome > $ptkp ? ($taxableIncome - $ptkp) * $taxRate : 0;

                // --- 5. FINAL CALCULATION ---
                // Catatan 3: Perbaikan logika Net Salary
                $totalDeduction = $attendanceDeduction + $taxAmount;
                $netSalary = $grossSalary - $totalDeduction;

                Payroll::create([
                    'employee_id' => $employee->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'base_salary' => $baseSalary,
                    'allowance_total' => $allowanceTotal,
                    'overtime_pay' => $overtimePay,
                    'late_deduction' => $lateDeduction,
                    'early_leave_deduction' => $earlyLeaveDeduction,
                    'total_deduction' => $totalDeduction,
                    'gross_salary' => $grossSalary,
                    'taxable_income' => $taxableIncome,
                    'ptkp' => $ptkp,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'net_salary' => $netSalary,
                    'status' => Payroll::STATUS_DRAFT,
                    'created_by_id' => 1, // Pastikan ID ini ada atau gunakan Auth jika manual
                ]);
            }
            DB::commit();
            $this->info('Payroll draft generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
