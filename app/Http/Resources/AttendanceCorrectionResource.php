<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = Auth::user();

        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee->user->name ?? null,
            'employee_nik' => $this->employee->nik ?? null,

            'attendance_date' => $this->attendance?->date?->format('Y-m-d'),
            'actual_clock_in' => $this->attendance?->clock_in?->format('H:i'),
            'actual_clock_out' => $this->attendance?->clock_out?->format('H:i'),

            'clock_in_requested' => $this->clock_in_requested?->format('H:i'),
            'clock_out_requested' => $this->clock_out_requested?->format('H:i'),
            'reason' => $this->reason,

            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'note' => $this->note,
            'approver_name' => $this->approver?->user?->name ?? null,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),

            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource) && $this->status === ApprovalStatus::PENDING->value,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Helper untuk label status agar konsisten dengan OvertimeResource
     */
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
