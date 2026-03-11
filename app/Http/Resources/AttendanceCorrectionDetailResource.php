<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCorrectionDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'attendance' => [
                'date' => $this->attendance?->date?->format('Y-m-d'),
                'actual_clock_in' => $this->attendance?->clock_in?->format('H:i:s'),
                'actual_clock_out' => $this->attendance?->clock_out?->format('H:i:s'),
                'current_status' => $this->attendance?->status,
            ],
            'employee' => [
                'name' => $this->employee?->user?->name,
                'nik' => $this->employee?->nik,
                'division' => $this->employee?->team?->divison?->name,
                'team' => $this->employee?->team?->name,
                'position' => $this->employee?->position?->name,
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
            ],
            'requested_times' => [
                'clock_in' => $this->clock_in_requested?->format('H:i:s'),
                'clock_out' => $this->clock_out_requested?->format('H:i:s'),
            ],
            'reason' => $this->reason,
            'attachment' => $this->attachment ? [
                'exists' => true,
                'filename' => basename($this->attachment),
                'path' => $this->attachment,
            ] : null,
            'status' => $this->status,
            'approval' => [
                'approved_by_name' => $this->approver?->user?->name,
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
                'note' => $this->note,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
