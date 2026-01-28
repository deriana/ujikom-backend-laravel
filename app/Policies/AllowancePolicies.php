<?php

namespace App\Policies;

use App\Models\Allowance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AllowancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('allowance.index');
    }

    public function view(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.index');
    }

    public function create(User $user): bool
    {
        return $user->can('allowance.create');
    }

    public function update(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.edit') && !$allowance->system_reserve;
    }

    public function delete(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.destroy') && !$allowance->system_reserve;
    }

    public function restore(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.restore');
    }

    public function forceDelete(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.forceDelete');
    }
}
