<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointItemResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'required_points' => (int) $this->required_points,
            'stock' => (int) $this->stock,
            'power_up_type' => $this->power_up_type?->value,
            'category' => $this->category,
            'is_active' => (bool) $this->is_active,
            'image_url' => $this->getFirstMediaUrl('point_item_images') ?: null,
        ];
    }
}
