<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\PayrollDetailResource;
use App\Models\Payroll;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayrollService
{
    public function index()
    {
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        $query = Payroll::with(['employee.user', 'employee.position'])->latest();

        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value,
            UserRole::FINANCE->value 
        ])) {
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee', function ($q) use ($currentUserEmployee) {
                $q->where('id', $currentUserEmployee->id)
                    ->orWhere('manager_id', $currentUserEmployee->id);
            });
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $query->where('employee_id', $currentUserEmployee->id);
        } else {
            return response()->json([], 200);
        }

        return $query->get();
    }

    public function show(Payroll $payroll): Payroll
    {
        return $payroll->load(['employee', 'employee.position.allowances']);
    }

    public function update(Payroll $payroll, array $data, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $data, $userId) {

            // 1. Validasi Status
            if ($payroll->isVoided()) {
                throw new \Exception('Cannot modify a voided payroll.');
            }

            if ($payroll->isFinalized()) {
                throw new \Exception('Finalized payroll cannot be modified.');
            }

            // 2. Ambil data adjustment
            $manualAdjustment = isset($data['manual_adjustment']) ? (float) $data['manual_adjustment'] : (float) $payroll->manual_adjustment;
            $adjustmentNote = $data['adjustment_note'] ?? $payroll->adjustment_note;

            // 3. Hitung Gross Salary (Sesuai logika Console)
            // Gross = Base + Allowance + Overtime + Manual Adjustment
            $grossSalary = (float) $payroll->base_salary
                + (float) $payroll->allowance_total
                + (float) $payroll->overtime_pay
                + $manualAdjustment;

            // 4. Hitung Taxable Income (PKP)
            // PKP = Gross - Total Deduction (Late/Early Leave)
            $taxableIncome = $grossSalary - (float) $payroll->total_deduction;

            // 5. Hitung Pajak (Sesuai logika Console dengan PTKP)
            $ptkp = 5000000; // Batas bebas pajak per bulan
            $taxRate = 0.05; // 5%

            if ($taxableIncome > $ptkp) {
                $taxAmount = ($taxableIncome - $ptkp) * $taxRate;
            } else {
                $taxAmount = 0;
            }

            // 6. Hitung Net Salary
            // Net = Gross - Total Deduction - Tax Amount
            $netSalary = $grossSalary - (float) $payroll->total_deduction - $taxAmount;

            // 7. Update Database
            $payroll->update([
                'manual_adjustment' => $manualAdjustment,
                'adjustment_note' => $adjustmentNote,
                'gross_salary' => $grossSalary,
                'taxable_income' => $taxableIncome,
                'tax_amount' => $taxAmount,
                'net_salary' => $netSalary,
                'updated_by_id' => $userId, // Pastikan kolom ini ada di fillable model
            ]);

            $payroll->notifyCustom(
                title: 'Payroll Updated',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been updated."
            );

            return $payroll;
        });
    }

    public function finalize(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll) {

            if ($payroll->isVoided()) {
                throw new \Exception('Cannot finalize a voided payroll.');
            }

            if ($payroll->isFinalized()) {
                throw new \Exception('Payroll already finalized.');
            }

            $payroll->finalize();
            $this->generateSlip($payroll);

            $payroll->notifyCustom(
                title: 'Payroll Finalized',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been finalized."
            );

            return $payroll;
        });
    }

    public function void(Payroll $payroll, string $note, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $note, $userId) {

            if ($payroll->isFinalized()) {
                throw new \Exception('Cannot void finalized payroll.');
            }

            if ($payroll->isVoided()) {
                throw new \Exception('Payroll already voided.');
            }

            $payroll->update([
                'is_void' => true,
                'void_note' => $note,
                'updated_by_id' => $userId,
            ]);

            $payroll->notifyCustom(
                title: 'Payroll Voided',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been voided. Reason: {$note}"
            );

            return $payroll;
        });
    }

    public function generateSlip(Payroll $payroll)
    {
        if (! $payroll->isFinalized()) {
            throw new \Exception('Payroll must be finalized.');
        }

        $payroll->load(
            'employee.user',
            'employee.position.allowances'
        );

        $data = (new PayrollDetailResource($payroll))->resolve();

        $setting = Setting::where('key', 'general')->first();
        $general = $setting?->values ?? [];

        $pdf = Pdf::loadView('pdf.payroll-slip', [
            'data' => $data,
            'company' => $general,
        ]);

        $fileName = "slips/{$payroll->uuid}.pdf";

        Storage::put($fileName, $pdf->output());

        $payroll->update([
            'slip_path' => $fileName,
            'slip_generated_at' => now(),
        ]);

        return $payroll;
    }
}
