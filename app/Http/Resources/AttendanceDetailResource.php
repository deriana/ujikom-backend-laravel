<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AttendanceDetailResource
 *
 * Resource class untuk mentransformasi detail model Attendance menjadi format JSON yang mendalam.
 */
class AttendanceDetailResource extends JsonResource
{
   /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi detail absensi termasuk data karyawan, waktu, foto, dan lokasi.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id, /**< Identifier unik record absensi */
            'employee' => [
                'nik' => $this->employee->nik ?? null, /**< NIK karyawan */
                'name' => $this->employee->user->name ?? null, /**< Nama karyawan */
                'email' => $this->employee->user->email ?? null, /**< Email karyawan */
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil karyawan */
            ],
            'date' => $this->date->format('Y-m-d'), /**< Tanggal absensi */
            'status' => $this->status, /**< Status absensi (present, late, etc.) */
            'clock_in' => $this->clock_in?->format('Y-m-d H:i:s'), /**< Waktu masuk (clock in) */
            'clock_out' => $this->clock_out?->format('Y-m-d H:i:s'), /**< Waktu keluar (clock out) */
            'late_minutes' => $this->late_minutes, /**< Durasi keterlambatan dalam menit */
            'early_leave_minutes' => $this->early_leave_minutes, /**< Durasi pulang awal dalam menit */
            'work_minutes' => $this->work_minutes, /**< Total durasi kerja dalam menit */
            'overtime_minutes' => $this->overtime_minutes, /**< Durasi lembur dalam menit */
            'clock_in_photo' => $this->clock_in_photo ? asset($this->clock_in_photo) : null, /**< URL foto saat clock in */
            'clock_out_photo' => $this->clock_out_photo ? asset($this->clock_out_photo) : null, /**< URL foto saat clock out */
            'location_in' => [
                'latitude' => $this->latitude_in, /**< Koordinat latitude saat clock in */
                'longitude' => $this->longitude_in, /**< Koordinat longitude saat clock in */
            ],
            'location_out' => [
                'latitude' => $this->latitude_out, /**< Koordinat latitude saat clock out */
                'longitude' => $this->longitude_out, /**< Koordinat longitude saat clock out */
            ],
            'is_corrected' => $this->is_corrected, /**< Flag apakah data telah dikoreksi */
            'correction' => $this->whenLoaded('attendanceCorrection', function () {
                /**< Detail data koreksi jika tersedia */
                return new AttendanceCorrectionDetailResource($this->attendanceCorrection);
            }),
        ];
    }
}
