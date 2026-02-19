<?php

namespace App\Console\Commands;

use App\Enums\EmploymentState;
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

    protected $description = 'Generate monthly payroll draft for active employees';

    public function handle(): int
    {
        $today = Carbon::today();
        $month = $this->option('month') ?? ($today->day < 26 ? $today->copy()->subMonth()->month : $today->month);
        $year = $this->option('year') ?? ($today->day < 26 && ! $this->option('month') ? $today->copy()->subMonth()->year : $today->year);

        $periodStart = Carbon::create($year, $month)->startOfMonth();
        $periodEnd = Carbon::create($year, $month)->endOfMonth();

        $this->info("Generating payroll for period {$periodStart->toDateString()} - {$periodEnd->toDateString()}");

        // 1. FILTER: Hanya ambil karyawan yang aktif, sudah join, dan kontrak belum habis
        $employees = Employee::whereHas('user.roles', function ($query) {
                $query->where('name', '!=', UserRole::OWNER->value);
            })
            ->where('employment_state', 'active') // Harus Aktif
            ->where('join_date', '<=', $periodEnd) // Sudah join sebelum periode berakhir
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('contract_end') // Kontrak selamanya
                      ->orWhere('contract_end', '>=', $periodStart); // Atau kontrak belum habis saat periode mulai
            })
            ->whereNull('resign_date') // Belum resign
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
                // Cek duplikasi payroll
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('period_start', $periodStart->toDateString())
                    ->where('period_end', $periodEnd->toDateString())
                    ->exists();

                if ($exists) continue;

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
                $totalDeduction = $attendanceDeduction + $taxAmount;
                $netSalary = $grossSalary - $totalDeduction;

                $payroll = Payroll::create([
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
                    'created_by_id' => 1,
                ]);

                // --- 6. NOTIFY CUSTOM ---
                // Kirim notifikasi ke karyawan bahwa slip gaji draft sudah tersedia
                $payroll->notifyCustom(
                    title: 'Payroll Draft Generated',
                    message: "Hello {$employee->user->name}, your payslip for the period {$periodStart->format('M Y')} has been generated (Draft). Please check the details.",
                    customUsers: collect([$employee->user])
                );
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
