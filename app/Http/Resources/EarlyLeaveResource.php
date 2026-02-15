<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EarlyLeaveResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee?->user?->name,
            'employee_nik' => $this->employee?->nik,
            'reason' => $this->reason,
            'date' => $this->attendance?->date->format('Y-m-d'),
            'minutes_early' => $this->minutes_early,
            'status' => $this->status,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
