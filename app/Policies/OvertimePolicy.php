<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class OvertimePolicy
{
    use HandlesAuthorization;

    private function isOwnerOrStaff(User $user, Overtime $overtime): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $overtime->employee_id === $userEmployeeId;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('overtime.index');
    }

    public function view(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.show');
    }

    public function create(User $user): bool
    {
        return $user->can('overtime.create');
    }

    public function update(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.edit') &&
               !$overtime->system_reserve &&
               $overtime->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $overtime);
    }

    public function delete(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.destroy') &&
               !$overtime->system_reserve &&
               $overtime->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $overtime);
    }

    public function approve(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.approve');
    }

    public function export(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.export');
    }
}
