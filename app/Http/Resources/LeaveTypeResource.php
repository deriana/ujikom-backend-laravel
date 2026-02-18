<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'default_days' => $this->default_days,
            'gender' => $this->gender,
            'requires_family_status' => $this->requires_family_status,

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    // 'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

        ];
    }
}
