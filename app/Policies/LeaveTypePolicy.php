<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('leave_type.index');
    }

    public function create(User $user): bool
    {
        return $user->can('leave_type.create');
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave_type.edit') && ! $leaveType->system_reserve;
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave_type.destroy') && ! $leaveType->system_reserve;
    }
}
