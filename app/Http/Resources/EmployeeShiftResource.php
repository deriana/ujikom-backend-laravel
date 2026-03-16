<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EmployeeShiftResource
 *
 * Resource class untuk mentransformasi model EmployeeShift menjadi format JSON,
 * menghubungkan karyawan dengan jadwal shift tertentu pada tanggal spesifik.
 */
class EmployeeShiftResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data shift karyawan termasuk detail template shift dan info karyawan.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik penugasan shift */
            'shift_date' => optional($this->shift_date)->format('Y-m-d'), /**< Tanggal pelaksanaan shift */

            'employee' => $this->whenLoaded('employee', function () {
                return [ /**< Data karyawan yang ditugaskan */
                    'uuid' => $this->employee->uuid, /**< Identifier unik karyawan */
                    'nik' => $this->employee->nik, /**< Nomor Induk Karyawan */
                    'name' => $this->employee->user?->name, /**< Nama lengkap karyawan */
                    'profile_photo' => $this->employee?->getFirstMediaUrl('profile_photo') ?? null, /**< URL foto profil */
                ];
            }),

            'shift_template' => $this->whenLoaded('shiftTemplate', function () {
                return [ /**< Detail template shift yang digunakan */
                    'uuid' => $this->shiftTemplate->uuid, /**< Identifier unik template shift */
                    'name' => $this->shiftTemplate->name, /**< Nama shift (misal: Shift Pagi) */
                    'start_time' => optional($this->shiftTemplate->start_time)->format('H:i'), /**< Jam mulai kerja */
                    'end_time' => optional($this->shiftTemplate->end_time)->format('H:i'), /**< Jam selesai kerja */
                ];
            }),

            'created_at' => $this->created_at, /**< Waktu pembuatan record */
        ];
    }
}
