<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class WorkScheduleResource
 *
 * Resource class untuk mentransformasi model WorkSchedule menjadi format JSON,
 * mencakup detail jam kerja, waktu istirahat, mode kerja, dan toleransi keterlambatan.
 */
class WorkScheduleResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data jadwal kerja beserta metadata terkait.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik jadwal kerja */
            'name' => $this->name, /**< Nama jadwal kerja (misal: Reguler, Ramadhan) */

            'work_mode' => [
                'id' => $this->workMode?->id, /**< ID mode kerja */
                'name' => $this->workMode?->name, /**< Nama mode kerja (misal: WFO, WFH) */
            ],

            'total_employees' => $this->whenCounted('employeeWorkSchedules'), /**< Jumlah karyawan yang menggunakan jadwal ini */

            'work_start_time' => $this->work_start_time ? Carbon::parse($this->work_start_time)->format('H:i') : null, /**< Jam mulai kerja */
            'work_end_time' => $this->work_end_time ? Carbon::parse($this->work_end_time)->format('H:i') : null, /**< Jam selesai kerja */
            'break_start_time' => $this->break_start_time ? Carbon::parse($this->break_start_time)->format('H:i') : null, /**< Jam mulai istirahat */
            'break_end_time' => $this->break_end_time ? Carbon::parse($this->break_end_time)->format('H:i') : null, /**< Jam selesai istirahat */
            'late_tolerance_minutes' => $this->late_tolerance_minutes, /**< Batas toleransi keterlambatan dalam menit */
            'requires_office_location' => (bool) $this->requires_office_location, /**< Status apakah memerlukan presensi di lokasi kantor */

            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat jadwal ini */
                return [
                    'id' => $this->creator?->id,
                    'name' => $this->creator?->name,
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at?->toDateTimeString(), /**< Waktu pembaruan terakhir */
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()), /**< Waktu penghapusan (soft delete) */
        ];
    }
}
