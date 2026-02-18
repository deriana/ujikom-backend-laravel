<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'start_time' => optional($this->start_time)->format('H:i'),
            'end_time' => optional($this->end_time)->format('H:i'),
            'cross_day' => $this->cross_day,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'employee_shifts_count' => $this->whenCounted('employeeShifts'),
        ];
    }
}
