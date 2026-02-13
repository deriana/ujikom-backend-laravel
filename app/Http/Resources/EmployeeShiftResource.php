<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'shift_date' => optional($this->shift_date)->format('Y-m-d'),

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'uuid' => $this->employee->uuid,
                    'nik' => $this->employee->nik,
                    'name' => $this->employee->user?->name,
                    'profile_photo' => $this->employee?->getFirstMediaUrl('profile_photo') ?? null,
                ];
            }),

            'shift_template' => $this->whenLoaded('shiftTemplate', function () {
                return [
                    'uuid' => $this->shiftTemplate->uuid,
                    'name' => $this->shiftTemplate->name,
                    'start_time' => optional($this->shiftTemplate->start_time)->format('H:i'),
                    'end_time' => optional($this->shiftTemplate->end_time)->format('H:i'),
                ];
            }),

            'created_at' => $this->created_at,
        ];
    }
}
