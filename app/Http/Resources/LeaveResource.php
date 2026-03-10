<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LeaveResource extends JsonResource
{
    public function toArray($request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $currentApprovalUuid = null;
        $canApprove = false;

        $pendingApproval = $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level', 'asc')
            ->first();

        if ($pendingApproval) {
            $isTargetApprover = $employee && $pendingApproval->approver_id === $employee->id;
            $isAdmin = $user->hasRole(UserRole::ADMIN);

            if ($isTargetApprover || $isAdmin) {
                $currentApprovalUuid = $pendingApproval->uuid;
                $canApprove = true;
            }
        }

        return [
            'uuid' => $this->uuid,
            'current_approval_uuid' => $currentApprovalUuid,
            'employee_name' => $this->employee->user->name ?? '-',
            'employee_nik' => $this->employee->nik ?? '-',
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
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $canApprove,
            ],
            'approval_levels' => $this->approvals->map(function ($approval) {
                return [
                    'level' => $approval->level,
                    'status' => $approval->status,
                    'nama_approver' => $approval->approver->user->name,
                ];
            }),
            'current_level' => $this->approvals->max('level'),
            'approval_status' => $this->approval_status,
            'is_half_day' => $this->is_half_day,
            'duration' => $this->duration,
            'duration_label' => $this->duration_text,
            'next_approver' => optional($this->nextApprover())->name,
        ];
    }
}
