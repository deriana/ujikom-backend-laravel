<?php

namespace App\Exports;

use App\Models\EarlyLeave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Class EarlyLeaveExport
 *
 * Menangani proses ekspor data pengajuan izin pulang cepat ke format Excel.
 */
class EarlyLeaveExport implements
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
        $query = EarlyLeave::query()
            ->with(['employee.user', 'attendance', 'approver.user']);

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

    /**
     * Memetakan setiap baris data dari model ke dalam format kolom Excel.
     *
     * @param mixed $earlyLeave Objek model EarlyLeave
     * @return array
     */
    public function map($earlyLeave): array
    {
        return [
            $earlyLeave->employee->nik ?? '-',
            $earlyLeave->employee->user->name ?? '-',
            $earlyLeave->attendance->date?->format('Y-m-d') ?? '-',
            $earlyLeave->attendance->clock_in?->format('H:i') ?? '-',
            $earlyLeave->minutes_early ?? 0,
            $earlyLeave->reason,
            $earlyLeave->status,
            $earlyLeave->approver->user->name ?? 'N/A',
            $earlyLeave->note ?? '-',
        ];
    }

    /**
     * Menentukan judul kolom (header) pada file Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'NIK',
            'Employee Name',
            'Attendance Date',
            'Clock In Time',
            'Minutes Early',
            'Reason',
            'Status',
            'Processed By',
            'Note',
        ];
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
