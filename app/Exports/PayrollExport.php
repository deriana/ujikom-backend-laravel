<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PayrollExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Payroll::query()->with(['employee.user']);

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('period_start', [
                $this->filters['start_date'],
                $this->filters['end_date']
            ]);
        }

        return $query->latest();
    }

    public function map($payroll): array
    {
        return [
            $payroll->uuid,
            $payroll->employee?->user?->name ?? '-',
            $payroll->employee?->nik ?? '-',
            $payroll->period_start->format('Y-m-d'),
            $payroll->period_end->format('Y-m-d'),
            $payroll->net_salary,
            $payroll->gross_salary,
            $payroll->manual_adjustment,
            $payroll->adjustment_note ?? '-',
            $payroll->getStatusLabel(),
            $payroll->finalized_at ? $payroll->finalized_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    public function headings(): array
    {
        return [
            'UUID',
            'Employee Name',
            'NIK',
            'Period Start',
            'Period End',
            'Net Salary',
            'Gross Salary',
            'Manual Adjustment',
            'Adjustment Note',
            'Status',
            'Finalized At',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
