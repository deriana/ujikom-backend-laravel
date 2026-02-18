<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employee = $this->employee;
        $position = $employee?->position;

        return [
            /*
            |--------------------------------------------------------------------------
            | Payroll Metadata
            |--------------------------------------------------------------------------
            */
            'uuid' => $this->uuid,
            'status' => [
                'code' => $this->status,
                'label' => $this->isDraft() ? 'Draft' : 'Finalized',
                'is_editable' => $this->isDraft(),
            ],
            'finalized_at' => $this->finalized_at,

            /*
            |--------------------------------------------------------------------------
            | Employee Information
            |--------------------------------------------------------------------------
            */
            'employee' => [
                'nik' => $employee?->nik,
                'name' => $employee?->user?->name,
                'phone' => $employee?->phone,
                'employment_status' => $employee?->employee_status?->label(),
                'join_date' => $employee?->join_date,
                'position' => [
                    'name' => $position?->name,
                    'base_salary_position' => $position?->base_salary,
                ],
                'profile_photo' => $employee?->getFirstMediaUrl('profile_photo') ?: null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            */
            'period' => [
                'start' => $this->period_start?->format('Y-m-d'),
                'end' => $this->period_end?->format('Y-m-d'),
                'days' => $this->period_start && $this->period_end
                    ? $this->period_start->diffInDays($this->period_end) + 1
                    : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Earnings (Pendapatan)
            |--------------------------------------------------------------------------
            */
            'earnings' => [
                'base_salary' => $this->base_salary,

                'allowances' => $position?->allowances?->map(function ($allowance) {
                    return [
                        'name' => $allowance->name,
                        'type' => $allowance->type,
                        'amount' => $allowance->pivot->amount ?? $allowance->amount,
                    ];
                })->values(),

                'allowance_total' => $this->allowance_total,
                'overtime_pay' => $this->overtime_pay,
                'manual_adjustment' => $this->manual_adjustment,

                'gross_salary' => $this->gross_salary,
            ],

            /*
            |--------------------------------------------------------------------------
            | Deductions (Potongan)
            |--------------------------------------------------------------------------
            */
            'deductions' => [
                'late_deduction' => $this->late_deduction,
                'early_leave_deduction' => $this->early_leave_deduction,
                'total_attendance_deduction' => ($this->late_deduction ?? 0) +
                    ($this->early_leave_deduction ?? 0),

                'tax_amount' => $this->tax_amount,

                'total_deduction' => $this->total_deduction,
            ],

            /*
            |--------------------------------------------------------------------------
            | Tax Summary
            |--------------------------------------------------------------------------
            */
            'tax_summary' => [
                'ptkp' => $this->ptkp,
                'taxable_income' => $this->taxable_income,
                'tax_rate_percent' => $this->tax_rate,
                'tax_rate_decimal' => $this->tax_rate
                    ? $this->tax_rate / 100
                    : null,
                'tax_amount' => $this->tax_amount,
            ],

            /*
            |--------------------------------------------------------------------------
            | Final Calculation
            |--------------------------------------------------------------------------
            */
            'summary' => [
                'gross_salary' => $this->gross_salary,
                'total_deduction' => $this->total_deduction,
                'net_salary' => $this->net_salary,
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes & Audit
            |--------------------------------------------------------------------------
            */
            'adjustment_note' => $this->adjustment_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
