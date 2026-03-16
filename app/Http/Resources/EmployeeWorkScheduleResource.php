<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EmployeeWorkScheduleResource
 *
 * Resource class untuk mentransformasi model EmployeeWorkSchedule menjadi format JSON,
 * yang menghubungkan karyawan dengan jadwal kerja tertentu beserta periode berlakunya.
 */
class EmployeeWorkScheduleResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data penugasan jadwal kerja karyawan termasuk detail profil dan status aktif.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik penugasan jadwal kerja */
            'employee' => [
                'uuid' => $this->employee?->uuid,
                'name' => $this->employee?->user?->name,
                'nik' => $this->employee?->nik,
                'profile_photo' => $this->employee?->getFirstMediaUrl('profile_photo') ?? null,
                'position' => [
                    'name' => $this->employee?->position?->name,
                ],
                'team' => [
                    'name' => $this->employee?->team?->name,
                    'division' => $this->employee?->team?->division?->name,
                ],
                'system_reserve' => $this->employee?->user?->system_reserve,
            ],
            'work_schedule' => [
                'uuid' => $this->workSchedule?->uuid, /**< Identifier unik jadwal kerja */
                'name' => $this->workSchedule?->name, /**< Nama jadwal kerja */
                'work_mode' => $this->workSchedule?->workMode?->name, /**< Mode kerja (WFO/WFH) */
            ],
            'start_date' => $this->start_date?->toDateString(), /**< Tanggal mulai berlakunya jadwal */
            'end_date' => $this->end_date?->toDateString(), /**< Tanggal berakhirnya jadwal (null jika permanen) */
            'priority' => $this->priority, /**< Tingkat prioritas jadwal */
            'is_active_today' => $this->start_date <= now() &&
                (is_null($this->end_date) || $this->end_date >= now()), /**< Status apakah jadwal aktif pada hari ini */
            'created_at' => $this->created_at?->toDateTimeString(), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at?->toDateTimeString(), /**< Waktu pembaruan terakhir */
        ];
    }
}
