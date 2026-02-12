<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeWorkScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'employee' => [
                'uuid' => $this->employee?->uuid,
                'name' => $this->employee?->user?->name,
                'nik' => $this->employee?->nik,
                'profile_photo' => $this->employee?->getFirstMediaUrl('profile_photo') ?? null,
            ],

            'work_schedule' => [
                'uuid' => $this->workSchedule?->uuid,
                'name' => $this->workSchedule?->name,
                'work_mode' => $this->workSchedule?->workMode?->name,
            ],

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),

            'is_active_today' => $this->start_date <= now() &&
                (is_null($this->end_date) || $this->end_date >= now()),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
