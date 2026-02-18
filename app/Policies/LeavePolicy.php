<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah user adalah pemilik data
     * atau memiliki role HR/Admin.
     */
    private function isOwnerOrStaff(User $user, Leave $leave): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $leave->employee_id == $userEmployeeId;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('leave.index');
    }

    public function view(User $user, Leave $leave): bool
    {
        return $user->can('leave.show');
    }

    public function create(User $user): bool
    {
        return $user->can('leave.create');
    }

    public function update(User $user, Leave $leave): bool
    {
        return $user->can('leave.edit') &&
               !$leave->system_reserve &&
               $leave->approval_status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $leave);
    }

    public function delete(User $user, Leave $leave): bool
    {
        return $user->can('leave.destroy') &&
               !$leave->system_reserve &&
               $leave->approval_status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $leave);
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $user->can('leave.approve');
    }
}
