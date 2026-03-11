<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AllowanceResource
 *
 * Resource class untuk mentransformasi model Allowance menjadi format JSON.
 */
class AllowanceResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data tunjangan termasuk relasi pembuat dan jabatan terkait
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik tunjangan */
            'name' => $this->name, /**< Nama tunjangan */
            'type' => $this->type, /**< Tipe tunjangan (misal: fixed, variable) */
            'amount' => (float) $this->amount, /**< Nilai nominal tunjangan */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat record ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'positions' => $this->whenLoaded('positions', function () { /**< Daftar jabatan yang mendapatkan tunjangan ini */
                return $this->positions->map(function ($position) {
                    return [
                        'uuid' => $position->uuid,
                        'name' => $position->name,
                        'amount' => isset($position->pivot->amount)
                            ? (float) $position->pivot->amount
                            : (float) $this->amount,
                    ];
                });
            }),
        ];
    }
}
