<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AttendanceLogResource
 *
 * Resource class untuk mentransformasi model AttendanceLog menjadi format JSON,
 * mencakup detail teknis aktivitas presensi seperti biometrik, lokasi, dan status aksi.
 */
class AttendanceLogResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data log presensi lengkap dengan informasi teknis dan lokasi.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, /**< Identifier unik log */
            'employee_id' => $this->employee_id, /**< ID internal karyawan */
            'employee_nik' => $this->employee_nik ?? '-', /**< NIK karyawan saat log dibuat */

            // Status dan Action
            'status' => $this->status, // success / failed
            'action' => $this->action ?? 'unknown',
            'reason' => $this->reason ?? 'No reason provided',

            // Biometrik & Teknis
            // Kita format score-nya jadi 2 angka di belakang koma, atau 0 jika null
            'similarity_score' => $this->similarity_score ? round($this->similarity_score, 2) : 0,
            'ip_address' => $this->ip_address ?? '0.0.0.0', 
            'user_agent' => $this->user_agent,

            // Lokasi
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            // Relasi (Hanya muncul jika di-load menggunakan with())
            'employee' => [
                'nik' => $this->employee->nik ?? null, /**< NIK karyawan */
                'name' => $this->employee->user->name ?? null, /**< Nama karyawan */
            ],
            // Waktu log dalam format yang mudah dibaca
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
