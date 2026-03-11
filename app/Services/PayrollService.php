<?php

namespace App\Services;

use App\Enums\EmployeeState;
use App\Enums\UserRole;
use App\Http\Resources\PayrollDetailResource;
use App\Jobs\GeneratePayrollSlipJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
     */
    public function show(Payroll $payroll): Payroll
    {
        $periodString = $payroll->period_start->format('Y-m');

        return $payroll->load([
            'employee.user',
            'employee.position.allowances',
            'employee.assessmentsAsEvaluatee' => function ($query) use ($periodString) {
                $query->where('period', 'like', $periodString.'%')
                    ->with('assessments_details');
            },
        ]);
    }

    /**
     * Store payroll records for specified employees in a given month.
     *
     * @throws \Exception
     */
    public function store(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $month = $data['month']; // Format: Y-m
            $employeeNiks = $data['employee_niks'];

            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            $employees = $this->getEligibleEmployees($employeeNiks, $periodStart, $periodEnd);

            $payrolls = collect();

            foreach ($employees as $employee) {
                // Check for duplicate payroll records for the same period
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('period_start', $periodStart->toDateString())
                    ->where('period_end', $periodEnd->toDateString())
                    ->where('status', '!=', Payroll::STATUS_VOIDED)
                    ->where('is_void', false)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $calculations = $this->calculatePayroll($employee, $periodStart, $periodEnd);

                $payroll = Payroll::create(array_merge($calculations, [
                    'employee_id' => $employee->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => Payroll::STATUS_DRAFT,
                    'created_by_id' => $userId,
                ]));

                // --- 6. SEND NOTIFICATION ---
                $payroll->notifyCustom(
                    title: 'Payroll Draft Generated',
                    message: "Hello {$employee->user->name}, your payslip for the period {$periodStart->format('M Y')} has been generated (Draft). Please check the details.",
                    customUsers: collect([$employee->user])
                );

                $payrolls->push($payroll);
            }

            return $payrolls;
        });
    }

    /**
     * Update an existing payroll record with manual adjustments and recalculate totals.
     *
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
     * Bulk finalize payroll records.
     */
    public function bulkFinalize(array $uuids): array
    {
        return DB::transaction(function () use ($uuids) {
            // 1. Retrieve payroll records with necessary relationships
            $payrolls = Payroll::with(['employee.user'])->whereIn('uuid', $uuids)->get();

            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            // 2. Iterate through each payroll and attempt to finalize
            foreach ($payrolls as $payroll) {
                try {
                    $this->finalize($payroll);
                    $results['success']++;
                } catch (\Exception $e) {
                    // 3. Track failures without breaking the entire batch
                    $results['failed']++;
                    $results['errors'][] = [
                        'uuid' => $payroll->uuid,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        });
    }

    /**
     * Void a payroll record with a reason.
     *
     * @throws \Exception
     */
    public function void(Payroll $payroll, string $note, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $note) {
            // 1. Validate state
            if ($payroll->isFinalized()) {
                throw new \Exception('Cannot void finalized payroll.');
            }

            if ($payroll->isVoided()) {
                throw new \Exception('Payroll already voided.');
            }

            // 2. Update record
            $payroll->void($note);

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
     * @return Payroll
     *
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

    /**
     * Calculate payroll for a single employee for a given period.
     */
    private function calculatePayroll(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $baseSalary = (float) ($employee->base_salary ?? 0);
        $hourlyRate = $baseSalary > 0 ? ($baseSalary / 173) : 0;

        // --- 1. CALCULATE ALLOWANCES ---
        $allowanceTotal = 0;
        if ($employee->position) {
            foreach ($employee->position->allowances as $allowance) {
                $amount = $allowance->pivot?->amount ?? $allowance->amount;
                $allowanceTotal += ($allowance->type === 'percentage')
                    ? $baseSalary * ($amount / 100)
                    : (float) $amount;
            }
        }

        // --- 2. CALCULATE OVERTIME ---
        $overtimeMinutes = $employee->overtimes->sum('duration_minutes');
        $overtimePay = ($overtimeMinutes / 60) * $hourlyRate;

        // --- 2.1 CALCULATE BONUS FROM ASSESSMENT ---
        $assessmentBonus = 0;
        foreach ($employee->assessmentsAsEvaluatee as $assessment) {
            // Sum bonus_salary from each assessment detail
            $assessmentBonus += $assessment->assessments_details->sum('bonus_salary');
        }

        $grossSalary = $baseSalary + $allowanceTotal + $overtimePay + $assessmentBonus;

        // --- 3. CALCULATE ATTENDANCE DEDUCTIONS (Late & Early Leave) ---
        $lateMinutes = $employee->attendances->sum('late_minutes');
        $earlyLeaveMinutes = $employee->attendances
            ->where('is_early_leave_approved', false)
            ->sum('early_leave_minutes');

        $lateDeduction = ($lateMinutes / 60) * $hourlyRate;
        $earlyLeaveDeduction = ($earlyLeaveMinutes / 60) * $hourlyRate;
        $attendanceDeduction = $lateDeduction + $earlyLeaveDeduction;

        // --- 4. TAX CALCULATION (Simplified PPh21) ---
        $taxableIncome = $grossSalary - $attendanceDeduction;
        $ptkp = 5000000;
        $taxRate = 0.05;
        $taxAmount = $taxableIncome > $ptkp ? ($taxableIncome - $ptkp) * $taxRate : 0;

        // --- 5. FINAL CALCULATION ---
        $totalDeduction = $attendanceDeduction + $taxAmount;
        $netSalary = $grossSalary - $totalDeduction;

        return [
            'base_salary' => $baseSalary,
            'allowance_total' => $allowanceTotal,
            'overtime_pay' => $overtimePay,
            'assessment_bonus' => $assessmentBonus,
            'late_deduction' => $lateDeduction,
            'early_leave_deduction' => $earlyLeaveDeduction,
            'total_deduction' => $totalDeduction,
            'gross_salary' => $grossSalary,
            'taxable_income' => $taxableIncome,
            'ptkp' => $ptkp,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'net_salary' => $netSalary,
        ];
    }

    /**
     * Fetch employees who are eligible for payroll in a specific period.
     */
    private function getEligibleEmployees(array $employeeNiks, Carbon $periodStart, Carbon $periodEnd)
    {
        $periodString = $periodStart->format('Y-m');

        return Employee::whereIn('nik', $employeeNiks)
            ->whereHas('user.roles', function ($query) {
                $query->where('name', '!=', UserRole::OWNER->value);
            })
            ->where('employment_state', EmployeeState::ACTIVE->value)
            ->where('join_date', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('contract_end')
                    ->orWhere('contract_end', '>=', $periodStart);
            })
            ->whereNull('resign_date')
            ->with([
                'position.allowances',
                'user',
                'attendances' => function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('date', [$periodStart, $periodEnd]);
                },
                'overtimes' => function ($q) use ($periodStart, $periodEnd) {
                    $q->approved()
                        ->whereHas('attendance', function ($query) use ($periodStart, $periodEnd) {
                            $query->whereBetween('date', [$periodStart, $periodEnd]);
                        });
                },
                'assessmentsAsEvaluatee' => function ($q) use ($periodString) {
                    $q->where('period', 'like', $periodString.'%')
                        ->with('assessments_details');
                },
            ])
            ->get();
    }

    public function generateMonthlyPayroll(Carbon $periodStart, Carbon $periodEnd, int $userId)
    {
        // Get all eligible employees' NIKs
        $eligibleEmployees = $this->getEligibleEmployeesForMonthly($periodStart, $periodEnd);
        $employeeNiks = $eligibleEmployees->pluck('nik')->toArray();

        // Prepare data for store method
        $data = [
            'month' => $periodStart->format('Y-m'),
            'employee_niks' => $employeeNiks,
        ];

        // Call the existing store method
        return $this->store($data, $userId);
    }

    /**
     * Fetch employees who are eligible for monthly payroll in a specific period (public version).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEligibleEmployeesForMonthly(Carbon $periodStart, Carbon $periodEnd)
    {
        return Employee::whereHas('user.roles', function ($query) {
            $query->where('name', '!=', UserRole::OWNER->value);
        })
            ->where('employment_state', EmployeeState::ACTIVE->value)
            ->where('join_date', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('contract_end')
                    ->orWhere('contract_end', '>=', $periodStart);
            })
            ->whereNull('resign_date')
            ->with([
                'position.allowances',
                'user',
                'attendances' => function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('date', [$periodStart, $periodEnd]);
                },
                'overtimes' => function ($q) use ($periodStart, $periodEnd) {
                    $q->approved()
                        ->whereHas('attendance', function ($query) use ($periodStart, $periodEnd) {
                            $query->whereBetween('date', [$periodStart, $periodEnd]);
                        });
                },
            ])
            ->get();
    }
}
