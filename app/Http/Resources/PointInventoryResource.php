<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PointInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'item_uuid' => $this->pointItem->uuid,
            'item_name' => $this->pointItem->name,
            'category' => $this->pointItem->category,
            'power_up_type' => $this->pointItem->power_up_type?->value,
            'description' => $this->pointItem->description,
            'image_url' => $this->pointItem->getFirstMediaUrl('point_item_images') ?: null,
            'is_used' => (bool) $this->is_used,
            'obtained_at' => $this->created_at->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at ? $this->expired_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
