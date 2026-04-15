<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik (UUID) */
            'category' => $this->category, /**< Kategori poin */
            'event_name' => $this->event_name, /**< Nama aktivitas/kejadian (misal: Tepat Waktu) */
            'points' => $this->points, /**< Jumlah poin yang diberikan */
            'operator' => $this->operator, /**< Operator perbandingan */
            'min_value' => $this->min_value, /**< Nilai ambang batas minimal */
            'max_value' => $this->max_value, /**< Nilai ambang batas maksimal */
            'is_active' => (bool) $this->is_active, /**< Status apakah aturan ini sedang berlaku */
            'description' => $this->description, /**< Penjelasan mengenai aturan poin */
        ];
    }
}
