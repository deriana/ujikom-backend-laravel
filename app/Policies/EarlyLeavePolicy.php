<?php

namespace App\Policies;

use App\Models\EarlyLeave;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus; 
use Illuminate\Auth\Access\HandlesAuthorization;

class EarlyLeavePolicy
{
    use HandlesAuthorization;

    private function isOwnerOrStaff(User $user, EarlyLeave $earlyLeave): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $earlyLeave->employee_id === $userEmployeeId;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('early-leave.index');
    }

    public function view(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.show');
    }

    public function create(User $user): bool
    {
        return $user->can('early-leave.create');
    }

    public function update(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.edit') &&
               !$earlyLeave->system_reserve &&
               $earlyLeave->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $earlyLeave);
    }

    public function delete(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.destroy') &&
               !$earlyLeave->system_reserve &&
               $earlyLeave->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $earlyLeave);
    }

    public function approve(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.approve');
    }

    public function export(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.export');
    }
}
