<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRequestPolicy
{
    use HandlesAuthorization;

    private function isOwnerOrStaff(User $user, AttendanceRequest $attendanceRequest): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $attendanceRequest->employee_id === $userEmployeeId;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('attendance-request.index');
    }

    public function view(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.show');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance-request.create');
    }

    public function update(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.edit') &&
               ! $attendanceRequest->system_reserve &&
               $attendanceRequest->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceRequest);
    }

    public function delete(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.destroy') &&
               ! $attendanceRequest->system_reserve &&
               $attendanceRequest->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceRequest);
    }

    public function approve(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.approve');
    }

    public function export(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.export');
    }
}
