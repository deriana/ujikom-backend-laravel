<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PositionResource
 *
 * Resource class untuk mentransformasi model Position menjadi format JSON,
 * mencakup informasi gaji pokok, status sistem, dan daftar tunjangan terkait.
 */
class PositionResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data jabatan beserta rincian gaji dan tunjangan.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik jabatan */
            'name' => $this->name, /**< Nama jabatan */
            'base_salary' => (float) $this->base_salary, /**< Gaji pokok standar untuk jabatan ini */
            'system_reserve' => $this->system_reserve, /**< Status apakah jabatan ini merupakan cadangan sistem */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat record ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'allowances' => $this->whenLoaded('allowances', function () { /**< Daftar tunjangan yang melekat pada jabatan ini */
                return $this->allowances->map(function ($allowance) {
                    return [
                        'uuid' => $allowance->uuid, /**< Identifier unik tunjangan */
                        'name' => $allowance->name,
                        'amount' => (float) ($allowance->pivot?->amount ?? $allowance->amount),
                    ];
                });
            }),
        ];
    }
}
