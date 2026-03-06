<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // Hitung sisa cuti dari EmployeeLeaveBalance
        $balance = $this->employee->leaveBalances()
            ->where('leave_type_id', $this->leave_type_id)
            ->first();

        return [
            'uuid' => $this->uuid,
            'employee' => [
                'name' => $this->employee->user->name ?? 'N/A',
                'nik' => $this->employee->nik ?? '-',
                'job_position' => $this->employee->position?->name ?? '-',
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?? null,
            ],
            'leave_type' => [
                'name' => $this->leaveType?->name ?? 'Unknown',
                'default_days' => $this->leaveType->default_days,
                'is_active' => $this->leaveType->is_active,
                'gender' => $this->leaveType->gender,
                'requires_family_status' => $this->leaveType->requires_family_status,
            ],
            'date_start' => $this->date_start->format('Y-m-d'),
            'date_end' => $this->date_end->format('Y-m-d'),
            'is_half_day' => $this->is_half_day,
            'reason' => $this->reason,
            'attachment' => $this->attachment ? [
                'exists' => true,
                'filename' => basename($this->attachment),
                'path' => $this->attachment,
                'download_url' => url('/api/leaves/download-attachment/'.basename($this->attachment)),
            ] : null,
            'approval_status' => $this->approval_status,
            'next_approver' => optional($this->nextApprover())->name,
            'approvals' => $this->approvals->map(function ($approval) {
                return [
                    'uuid' => $approval->uuid,
                    'approver' => [
                        'name' => $approval->approver?->name ?? 'System',
                        // 'role' => $approval->approver->role,
                    ],
                    'level' => $approval->level,
                    'status' => $approval->status,
                    'approved_at' => optional($approval->approved_at)->format('Y-m-d H:i:s'),
                    'note' => $approval->note,
                ];
            }),
            'employee_leave_detail' => optional($this->employeeLeave)->only([
                'start_date', 'end_date', 'days_taken', 'status',
            ]),
            'leave_balance' => $balance ? [
                'year' => $balance->year,
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->getRemainingDaysAttribute(),
            ] : null,
        ];
    }
}
