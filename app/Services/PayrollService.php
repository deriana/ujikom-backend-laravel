<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\PayrollDetailResource;
use App\Jobs\GeneratePayrollSlipJob;
use App\Models\Payroll;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayrollService
{
    /**
     * Get a list of payroll records based on user roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Identify the current user and their employee profile
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        // 2. Initialize query with necessary relationships
        $query = Payroll::with(['employee.user', 'employee.position'])->latest();

        // 3. Apply role-based filtering
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // High-level roles can see all data
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            // Managers see their own and their subordinates' payroll
            $query->whereHas('employee', function ($q) use ($currentUserEmployee) {
                $q->where('id', $currentUserEmployee->id)
                    ->orWhere('manager_id', $currentUserEmployee->id);
            });
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value)) {
            // Employees only see their own payroll
            $query->where('employee_id', $currentUserEmployee->id);
        } else {
            return response()->json([], 200);
        }

        return $query->get();
    }

    /**
     * Show details of a specific payroll record.
     *
     * @param Payroll $payroll
     * @return Payroll
     */
    public function show(Payroll $payroll): Payroll
    {
        // 1. Load nested relationships for detailed view
        return $payroll->load(['employee', 'employee.position.allowances']);
    }

    /**
     * Update an existing payroll record with manual adjustments and recalculate totals.
     *
     * @param Payroll $payroll
     * @param array $data
     * @param int $userId
     * @return Payroll
     * @throws \Exception
     */
    public function update(Payroll $payroll, array $data, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $data, $userId) {
            // 1. Validate payroll status before modification
            if ($payroll->isVoided()) {
                throw new \Exception('Cannot modify a voided payroll.');
            }

            if ($payroll->isFinalized()) {
                throw new \Exception('Finalized payroll cannot be modified.');
            }

            // 2. Retrieve adjustment data from input or existing record
            $manualAdjustment = isset($data['manual_adjustment']) ? (float) $data['manual_adjustment'] : (float) $payroll->manual_adjustment;
            $adjustmentNote = $data['adjustment_note'] ?? $payroll->adjustment_note;

            // 3. Calculate Gross Salary
            $grossSalary = (float) $payroll->base_salary
                + (float) $payroll->allowance_total
                + (float) $payroll->overtime_pay
                + $manualAdjustment;

            // 4. Calculate Taxable Income (PKP)
            $taxableIncome = $grossSalary - (float) $payroll->total_deduction;

            // 5. Calculate Tax Amount based on PTKP threshold
            $ptkp = 5000000; // Monthly tax-free threshold
            $taxRate = 0.05; // 5% rate

            if ($taxableIncome > $ptkp) {
                $taxAmount = ($taxableIncome - $ptkp) * $taxRate;
            } else {
                $taxAmount = 0;
            }

            // 6. Calculate Net Salary (Take-home pay)
            $netSalary = $grossSalary - (float) $payroll->total_deduction - $taxAmount;

            // 7. Update Database
            $payroll->update([
                'manual_adjustment' => $manualAdjustment,
                'adjustment_note' => $adjustmentNote,
                'gross_salary' => $grossSalary,
                'taxable_income' => $taxableIncome,
                'tax_amount' => $taxAmount,
                'net_salary' => $netSalary,
                'updated_by_id' => $userId,
            ]);

            // 8. Send notification to the employee
            $payroll->notifyCustom(
                title: 'Payroll Updated',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been updated."
            );

            return $payroll;
        });
    }

    /**
     * Finalize a payroll record and trigger slip generation.
     *
     * @param Payroll $payroll
     * @return Payroll
     * @throws \Exception
     */
    public function finalize(Payroll $payroll): Payroll
    {
        DB::transaction(function () use ($payroll) {
            // 1. Validate state
            if ($payroll->isVoided()) {
                throw new \Exception('Cannot finalize a voided payroll.');
            }

            if ($payroll->isFinalized()) {
                throw new \Exception('Payroll already finalized.');
            }

            // 2. Update status to finalized
            $payroll->finalize();

            // 3. Send notification
            $payroll->notifyCustom(
                title: 'Payroll Finalized',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been finalized."
            );
        });

        // 4. Dispatch background job to generate PDF slip
        GeneratePayrollSlipJob::dispatch($payroll);

        return $payroll;
    }

    /**
     * Void a payroll record with a reason.
     *
     * @param Payroll $payroll
     * @param string $note
     * @param int $userId
     * @return Payroll
     * @throws \Exception
     */
    public function void(Payroll $payroll, string $note, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $note, $userId) {
            // 1. Validate state
            if ($payroll->isFinalized()) {
                throw new \Exception('Cannot void finalized payroll.');
            }

            if ($payroll->isVoided()) {
                throw new \Exception('Payroll already voided.');
            }

            // 2. Update record with void status and note
            $payroll->update([
                'is_void' => true,
                'void_note' => $note,
                'updated_by_id' => $userId,
            ]);

            // 3. Send notification
            $payroll->notifyCustom(
                title: 'Payroll Voided',
                message: "The payroll for period {$payroll->period_start->format('M Y')} has been voided. Reason: {$note}"
            );

            return $payroll;
        });
    }

    /**
     * Generate a PDF payroll slip and store it.
     *
     * @param Payroll $payroll
     * @return Payroll
     * @throws \Exception
     */
    public function generateSlip(Payroll $payroll)
    {
        // 1. Ensure payroll is finalized before generating slip
        if (! $payroll->isFinalized()) {
            throw new \Exception('Payroll must be finalized.');
        }

        // 2. Load necessary data for the PDF
        $payroll->load(
            'employee.user',
            'employee.position.allowances'
        );

        // 3. Prepare data using resource and fetch company settings
        $data = (new PayrollDetailResource($payroll))->resolve();
        $setting = Setting::where('key', 'general')->first();
        $general = $setting?->values ?? [];

        // 4. Generate PDF from view
        $pdf = Pdf::loadView('pdf.payroll-slip', [
            'data' => $data,
            'company' => $general,
        ]);

        // 5. Store the PDF file in private storage
        $fileName = "slips/{$payroll->uuid}.pdf";
        Storage::put($fileName, $pdf->output());

        // 6. Update payroll record with slip metadata
        $payroll->update([
            'slip_path' => $fileName,
            'slip_generated_at' => now(),
        ]);

        return $payroll;
    }
}
