<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,

            'work_mode' => [
                'id' => $this->workMode?->id,
                'name' => $this->workMode?->name,
            ],

            'total_employees' => $this->whenCounted('employeeWorkSchedules'),

            'work_start_time' => $this->work_start_time ? Carbon::parse($this->work_start_time)->format('H:i') : null,
            'work_end_time' => $this->work_end_time ? Carbon::parse($this->work_end_time)->format('H:i') : null,
            'break_start_time' => $this->break_start_time ? Carbon::parse($this->break_start_time)->format('H:i') : null,
            'break_end_time' => $this->break_end_time ? Carbon::parse($this->break_end_time)->format('H:i') : null,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'requires_office_location' => (bool) $this->requires_office_location,

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator?->id,
                    'name' => $this->creator?->name,
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}
