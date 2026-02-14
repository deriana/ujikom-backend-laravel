<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee->user->name,
            'employee_nik' => $this->employee->nik,
            'leave_type_uuid' => $this->leaveType->uuid,
            'leave_type' => $this->leaveType->name,
            'date_start' => $this->date_start->format('Y-m-d'),
            'date_end' => $this->date_end->format('Y-m-d'),
            'reason' => $this->reason,
            'attachment' => $this->attachment ? [
                'exists' => true,
                'filename' => basename($this->attachment),
                'path' => $this->attachment,
            ] : null,

            'approval_status' => $this->approval_status,
            'is_half_day' => $this->is_half_day,
            'next_approver' => optional($this->nextApprover())->name,
        ];
    }
}
