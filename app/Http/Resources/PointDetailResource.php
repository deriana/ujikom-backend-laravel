<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointDetailResource extends JsonResource
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
            'points' => $this->current_points,
            // 'note' => $this->note,
            'employee_name' => $this->employee->user->name,
            'event' => $this->rule->event_name,
            'description' => $this->rule->description,
            'period_name' => $this->period->name,
            // 'status' => [
            //     'is_void' => $this->is_void,
            //     'void_reason' => $this->void_reason,
            //     'voided_at' => $this->voided_at ? $this->voided_at->format('d M Y H:i') : null,
            // ],
            'timestamp' => $this->created_at->diffForHumans(),
        ];
    }
}
