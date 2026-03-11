<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ShiftTemplateResource
 *
 * Resource class untuk mentransformasi model ShiftTemplate menjadi format JSON,
 * mencakup detail waktu kerja, toleransi keterlambatan, dan informasi pembuat.
 */
class ShiftTemplateResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data template shift beserta metadata terkait.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik template shift */
            'name' => $this->name, /**< Nama shift (misal: Shift Pagi, Shift Malam) */
            'start_time' => optional($this->start_time)->format('H:i'), /**< Jam mulai kerja */
            'end_time' => optional($this->end_time)->format('H:i'), /**< Jam selesai kerja */
            'cross_day' => $this->cross_day, /**< Status apakah shift melewati pergantian hari (lintas hari) */
            'late_tolerance_minutes' => $this->late_tolerance_minutes, /**< Batas toleransi keterlambatan dalam menit */

            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat template ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at, /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at, /**< Waktu pembaruan terakhir */

            'employee_shifts_count' => $this->whenCounted('employeeShifts'), /**< Jumlah total penugasan shift yang menggunakan template ini */
        ];
    }
}
