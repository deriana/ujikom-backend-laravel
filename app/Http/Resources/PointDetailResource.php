<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'points' => (int) $this->current_points,
            'note' => $this->note,
            'event' => $this->rule->event_name ?? 'N/A',
            'category' => $this->rule->category?->value, // Tampilkan kategori (misal: attendance)
            'description' => $this->rule->description,
            'period_name' => $this->period->name ?? 'N/A',
            'timestamp' => $this->created_at->diffForHumans(),
            'formatted_date' => $this->created_at->format('d M Y H:i'),
        ];
    }
}
