<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class OvertimeResource extends JsonResource
{
    /**
     * Transform the resource into an array for table listing
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($myEmployeeId && $requesterManagerId == $myEmployeeId)
                      && ($this->status === ApprovalStatus::PENDING->value);

        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee->user->name ?? null,
            'employee_nik' => $this->employee->nik ?? null,
            'date' => optional($this->attendance)->date,
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'approved_by' => $this->manager->name ?? null,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource),
            ],
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
