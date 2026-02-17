<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LeaveResource extends JsonResource
{
    public function toArray($request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Jika user tidak punya data employee, kembalikan data standar
        if (!$employee) {
            return parent::toArray($request);
        }

        $currentApprovalUuid = null;
        $canApprove = false;

        // 1. Cari tahap approval yang sedang PENDING dan level paling rendah (tahap saat ini)
        $pendingApproval = $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level', 'asc')
            ->first();

        // 2. Validasi apakah user yang login adalah orang yang berhak approve (berdasarkan employee_id)
        if ($pendingApproval) {
            // Cek kecocokan EMPLOYEE ID, bukan User ID
            if ($pendingApproval->approver_id === $employee->id) {
                $currentApprovalUuid = $pendingApproval->uuid;
                $canApprove = true;
            }
        }

        return [
            'uuid' => $this->uuid,
            'current_approval_uuid' => $currentApprovalUuid,
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
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                // SEKARANG HANYA TRUE JIKA EMPLOYEE ID COCOK DENGAN TARGET APPROVER
                'approve' => $canApprove,
            ],
            'approval_status' => $this->approval_status,
            'is_half_day' => $this->is_half_day,
            'next_approver' => optional($this->nextApprover())->name,
        ];
    }
}
