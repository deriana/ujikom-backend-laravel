<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AttendanceResource
 *
 * Resource class untuk mentransformasi model Attendance menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class AttendanceResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data absensi harian karyawan secara ringkas.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id, /**< Identifier unik record absensi */
            'employee' => [
                'nik' => $this->employee->nik ?? null, /**< NIK karyawan */
                'name' => $this->employee->user->name ?? null, /**< Nama karyawan */
            ],
            'date' => $this->date->format('Y-m-d'), /**< Tanggal absensi */
            'status' => $this->status, /**< Status kehadiran (misal: present, late, absent) */
            'clock_in' => $this->clock_in?->format('Y-m-d H:i:s'), /**< Waktu masuk (clock in) */
            'clock_out' => $this->clock_out?->format('Y-m-d H:i:s'), /**< Waktu keluar (clock out) */
            'late_minutes' => $this->late_minutes, /**< Durasi keterlambatan dalam menit */
            'work_minutes' => $this->work_minutes, /**< Total durasi kerja dalam menit */
            'overtime_minutes' => $this->overtime_minutes, /**< Durasi lembur dalam menit */
        ];
    }
}
