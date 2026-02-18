<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AttendancesExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithChunkReading
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Attendance::query()
            ->with(['employee.user']); // penting untuk hindari N+1

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('date', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        return $query->orderBy('date', 'desc');
    }

    public function map($attendance): array
    {
        return [
            $attendance->employee->nik ?? null,
            $attendance->employee->user->name ?? null,
            $attendance->date?->format('Y-m-d'),
            $attendance->status,
            $attendance->clock_in?->format('Y-m-d H:i:s'),
            $attendance->clock_out?->format('Y-m-d H:i:s'),
            $attendance->late_minutes,
            $attendance->early_leave_minutes,
            $attendance->work_minutes,
            $attendance->overtime_minutes,
        ];
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Employee Name',
            'Date',
            'Status',
            'Clock In',
            'Clock Out',
            'Late Minutes',
            'Early Leave Minutes',
            'Work Minutes',
            'Overtime Minutes',
        ];
    }

    public function chunkSize(): int
    {
        return 1000; // proses per 1000 row
    }
}
