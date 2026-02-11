<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,

            // Untuk FE supaya mudah dipakai
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date'   => $this->end_date?->format('Y-m-d'),

            // Backward compatibility kalau FE lama masih pakai "date"
            'date' => $this->start_date?->format('Y-m-d'),

            'is_recurring' => (bool) $this->is_recurring,

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'uuid'  => $this->creator->uuid,
                    'name'  => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
