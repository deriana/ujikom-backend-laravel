<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class LeaveTypeResource
 *
 * Resource class untuk mentransformasi model LeaveType menjadi format JSON,
 * mencakup konfigurasi jatah cuti, batasan gender, dan status persyaratan keluarga.
 */
class LeaveTypeResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik tipe cuti */
            'name' => $this->name, /**< Nama tipe cuti (misal: Cuti Tahunan, Cuti Melahirkan) */
            'is_active' => $this->is_active, /**< Status apakah tipe cuti ini aktif dan dapat digunakan */
            'default_days' => $this->default_days, /**< Jumlah jatah hari default per tahun */
            'gender' => $this->gender, /**< Batasan gender (male/female/both) untuk tipe cuti ini */
            'requires_family_status' => $this->requires_family_status, /**< Status apakah memerlukan verifikasi status keluarga */

            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat tipe cuti ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    // 'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
