<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AssessmentCategoryResource
 *
 * Resource class untuk mentransformasi model AssessmentCategory menjadi format JSON.
 */
class AssessmentCategoryResource extends JsonResource
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
            'uuid' => $this->uuid, /**< Identifier unik kategori penilaian */
            'name' => $this->name, /**< Nama kategori penilaian */
            'description' => $this->description ?? '', /**< Deskripsi detail mengenai kategori */
            'is_active' => $this->is_active, /**< Status aktif/tidaknya kategori */

            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat kategori ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
