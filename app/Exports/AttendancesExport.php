<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Class AttendancesExport
 *
 * Menangani proses ekspor data absensi ke format Excel menggunakan library Maatwebsite Excel.
 */
class AttendancesExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithChunkReading
{
    protected array $filters; /**< Filter pencarian data absensi (seperti rentang tanggal) */

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
        $query = Attendance::query()
            ->with(['employee.user']);

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('date', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        return $query->orderBy('date', 'desc');
    }

    /**
     * Memetakan setiap baris data dari model ke dalam format kolom Excel.
     *
     * @param mixed $attendance Objek model Attendance
     * @return array
     */
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
