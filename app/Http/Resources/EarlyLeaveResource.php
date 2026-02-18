<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EarlyLeaveResource extends JsonResource
{
    public function toArray($request)
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($requesterManagerId == $myEmployeeId) && ($this->status === ApprovalStatus::PENDING->value);
        $isManager = ($this->employee?->manager_id == $myEmployeeId);
        $isDirector = $user->hasRole(UserRole::DIRECTOR->value);
        $isHr = $user->hasRole(UserRole::HR->value);

        $canApprove = ($this->status === ApprovalStatus::PENDING->value) && ($isManager || $isDirector || $isHr);

        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee?->user?->name,
            'employee_nik' => $this->employee?->nik,
            'reason' => $this->reason,
            'date' => $this->attendance?->date->format('Y-m-d'),
            'minutes_early' => $this->minutes_early,
            'status' => $this->status,
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                // 'approve' => $user->can('approve', $this->resource) && $isApprover,
                'approve' => $user->can('approve', $this->resource)
            ],
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
