<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DivisionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('division.index');
    }

    public function view(User $user, Division $division): bool
    {
        return $user->can('division.index');
    }

    public function create(User $user): bool
    {
        return $user->can('division.create');
    }

    public function update(User $user, Division $division): bool
    {
        return $user->can('division.edit') && !$division->system_reserve;
    }

    public function delete(User $user, Division $division): bool
    {
        return $user->can('division.destroy') && !$division->system_reserve;
    }

    public function restore(User $user, Division $division): bool
    {
        return $user->can('division.restore');
    }

    public function forceDelete(User $user, Division $division): bool
    {
        return $user->can('division.forceDelete');
    }
}
