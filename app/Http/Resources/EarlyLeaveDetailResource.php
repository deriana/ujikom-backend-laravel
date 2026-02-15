<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EarlyLeaveDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'date' => $this->attendance?->date?->format('Y-m-d'),
            'employee' => [
                'name' => $this->employee?->user?->name,
                'nik' => $this->employee?->nik,
            ],
            'minutes_early' => $this->minutes_early,
            'reason' => $this->reason,
            'attachment' => $this->attachment ? [
                'exists' => true,
                'filename' => basename($this->attachment),
                'path' => $this->attachment,
            ] : null,
            'status' => $this->status,
            'approval' => [
                'approved_by_name' => $this->manager?->user?->name,
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
                'note' => $this->note,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
