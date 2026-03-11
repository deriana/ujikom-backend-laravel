<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        // Simulasi angka finansial
        $baseSalary = $this->faker->numberBetween(5000000, 15000000);
        $allowance = $this->faker->numberBetween(500000, 2000000);
        $overtime = $this->faker->numberBetween(100000, 500000);
        $lateDeduction = $this->faker->numberBetween(0, 50000);

        $grossSalary = $baseSalary + $allowance + $overtime;
        $totalDeduction = $lateDeduction;
        $netSalary = $grossSalary - $totalDeduction;

        return [
            'uuid' => (string) Str::uuid(),
            'employee_id' => Employee::factory(), // Otomatis buat Employee jika tidak dipassing
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),

            // Finansial
            'base_salary' => $baseSalary,
            'allowance_total' => $allowance,
            'overtime_pay' => $overtime,
            'assessment_bonus' => 0,
            'manual_adjustment' => 0,
            'gross_salary' => $grossSalary,

            // Potongan
            'late_deduction' => $lateDeduction,
            'early_leave_deduction' => 0,
            'total_deduction' => $totalDeduction,

            // Pajak & Bersih
            'taxable_income' => $grossSalary,
            'tax_rate' => 0.05,
            'tax_amount' => $grossSalary * 0.05,
            'ptkp' => 4500000,
            'net_salary' => $netSalary,

            // Status & Metadata
            'status' => Payroll::STATUS_DRAFT,
            'is_void' => false,
            'created_by_id' => User::factory(),
            'updated_by_id' => User::factory(),
        ];
    }

    /**
     * State untuk Payroll yang sudah Final (Sudah Bayar)
     */
    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payroll::STATUS_FINALIZED,
            'finalized_at' => now(),
            'slip_path' => 'slips/' . Str::random(10) . '.pdf',
            'slip_generated_at' => now(),
        ]);
    }

    /**
     * State untuk Payroll yang dibatalkan (Void)
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payroll::STATUS_VOIDED,
            'is_void' => true,
            'void_note' => 'Kesalahan perhitungan jam lembur',
        ]);
    }
}
