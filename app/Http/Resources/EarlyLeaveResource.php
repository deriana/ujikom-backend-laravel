<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
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
                'approve' => $user->can('approve', $this->resource) && $isApprover,
            ],
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
