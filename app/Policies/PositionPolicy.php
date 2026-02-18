<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('position.index');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('position.show');
    }

    public function create(User $user): bool
    {
        return $user->can('position.create');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('position.edit') && ! $position->system_reserve;
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('position.destroy') && ! $position->system_reserve;
    }

    public function restore(User $user, Position $position): bool
    {
        return $user->can('position.restore');
    }

    public function forceDelete(User $user, Position $position): bool
    {
        return $user->can('position.forceDelete');
    }
}
