<?php

namespace App\Policies;

use App\Models\EarlyLeave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EarlyLeavePolicy
{
    use HandlesAuthorization;

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
        return $user->can('early-leave.edit') && !$earlyLeave->system_reserve;
    }

    public function delete(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.destroy') && !$earlyLeave->system_reserve;
    }

    public function approve(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.approve');
    }
}
