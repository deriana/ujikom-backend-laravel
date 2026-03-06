<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AttendanceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($myEmployeeId && $requesterManagerId == $myEmployeeId)
                      && ($this->status === ApprovalStatus::PENDING->value);

        return [
            'uuid' => $this->uuid,
            'request_type' => $this->request_type,
            'reason' => $this->reason,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'employee' => [
                'name' => $this->employee?->user?->name,
                'nik' => $this->employee?->nik,
            ],
            'shift_details' => $this->when($this->shift_template_id, function () {
                return [
                    'uuid' => $this->shiftTemplate?->uuid,
                    'name' => $this->shiftTemplate?->name,
                ];
            }),
            'work_schedule_details' => $this->when($this->work_schedules_id, function () {
                return [
                    'uuid' => $this->workSchedule?->uuid,
                    'name' => $this->workSchedule?->name,
                ];
            }),
            'approved_by' => $this->when($this->approved_by_id, function () {
                return [
                    'name' => $this->approver?->user?->name ?? 'System',
                ];
            }),
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource),
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
