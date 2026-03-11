<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class HolidayResource
 *
 * Resource class untuk mentransformasi model Holiday menjadi format JSON,
 * mencakup detail hari libur, rentang tanggal, dan informasi pembuat.
 */
class HolidayResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data hari libur termasuk status perulangan tahunan.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik hari libur */
            'name' => $this->name, /**< Nama atau deskripsi hari libur */

            'start_date' => $this->start_date?->format('Y-m-d'), /**< Tanggal mulai libur */
            'end_date'   => $this->end_date?->format('Y-m-d'), /**< Tanggal berakhir libur */

            'date' => $this->start_date?->format('Y-m-d'), /**< Alias untuk tanggal mulai (untuk kompatibilitas kalender) */

            'is_recurring' => (bool) $this->is_recurring, /**< Status apakah libur berulang setiap tahun */

            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat record ini */
                return [
                    'uuid'  => $this->creator->uuid,
                    'name'  => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
