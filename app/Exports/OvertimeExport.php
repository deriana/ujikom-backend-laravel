<?php

namespace App\Exports;

use App\Models\Overtime;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Class OvertimeExport
 *
 * Menangani proses ekspor data pengajuan lembur ke format Excel.
 */
class OvertimeExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithChunkReading
{
    protected array $filters;

    /**
     * Membuat instance export baru dengan filter tertentu.
     *
     * @param array $filters Array berisi parameter filter (start_date, end_date)
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Menyiapkan query database untuk mengambil data yang akan diekspor.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = Overtime::query()
            ->with(['employee.user', 'attendance', 'manager.user']);

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereHas('attendance', function ($q) {
                $q->whereBetween('date', [
                    Carbon::parse($this->filters['start_date'])->toDateString(),
                    Carbon::parse($this->filters['end_date'])->toDateString(),
                ]);
            });
        }

        return $query->latest();
    }

    public function map($overtime): array
    {
        return [
            $overtime->employee->nik ?? '-',
            $overtime->employee->user->name ?? '-',
            $overtime->attendance->date?->format('Y-m-d') ?? '-',
            $overtime->duration_minutes ?? 0,
            $overtime->reason,
            $overtime->status,
            $overtime->manager->user->name ?? 'N/A',
            $overtime->approved_at?->format('Y-m-d H:i:s') ?? '-',
            $overtime->note ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Employee Name',
            'Attendance Date',
            'Duration (Minutes)',
            'Reason',
            'Status',
            'Approved By',
            'Approved At',
            'Note',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
