<?php

namespace App\Exports;

use App\Models\Leave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Class LeaveExport
 *
 * Menangani proses ekspor data pengajuan cuti ke format Excel menggunakan library Maatwebsite Excel.
 */
class LeaveExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithChunkReading
{
    protected array $filters; /**< Filter pencarian data cuti (seperti rentang tanggal) */

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
        $query = Leave::query()
            ->with(['employee.user', 'leaveType']);

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('date_start', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        return $query->orderBy('date_start', 'desc');
    }

    /**
     * Memetakan setiap baris data dari model ke dalam format kolom Excel.
     *
     * @param mixed $leave Objek model Leave
     * @return array
     */
    public function map($leave): array
    {
        return [
            $leave->employee->nik ?? '-',
            $leave->employee->user->name ?? '-',
            $leave->leaveType->name ?? '-',
            $leave->date_start?->format('Y-m-d'),
            $leave->date_end?->format('Y-m-d'),
            $leave->duration . ' Day(s)',
            $leave->approval_status,
            $leave->reason,
            $leave->is_half_day ? 'Yes' : 'No',
            $leave->nextApprover()?->user->name ?? 'N/A',
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
            'Leave Type',
            'Start Date',
            'End Date',
            'Duration (Days)',
            'Approval Status',
            'Reason',
            'Is Half Day',
            'Waiting For',
        ];
    }

    /**
     * Menentukan jumlah baris yang diproses per batch untuk efisiensi memori.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
