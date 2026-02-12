<?php

namespace App\Http\Resources;

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

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}
