<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;

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
        return $user->can('leave.edit') && ! $leave->system_reserve;
    }

    public function delete(User $user, Leave $leave): bool
    {
        return $user->can('leave.destroy') && ! $leave->system_reserve;
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $user->can('leave.approve');
    }
}
