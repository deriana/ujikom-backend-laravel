<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array for show/detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'employee' => [
                'name' => $this->employee->user->name ?? null,
                'nik' => $this->employee->nik ?? null,
                'team' => $this->employee->team?->name,
                'division' => $this->employee->team?->division?->name,
                'position' => $this->employee->position?->name,
            ],
            'attendance' => [
                'date' => $this->attendance?->date?->format('Y-m-d'),
                'clock_in' => $this->attendance?->clock_in?->format('H:i:s'),
                'clock_out' => $this->attendance?->clock_out?->format('H:i:s'),
            ],
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'approved_by' => [
                'name' => $this->manager->user->name ?? null,
            ],
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'note' => $this->note,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            ApprovalStatus::PENDING->value => 'Pending',
            ApprovalStatus::APPROVED->value => 'Approved',
            ApprovalStatus::REJECTED->value => 'Rejected',
            default => 'Unknown',
        };
    }
}
