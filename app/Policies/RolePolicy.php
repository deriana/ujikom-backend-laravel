<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('role.index');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('role.index');
    }

    public function create(User $user): bool
    {
        return $user->can('role.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('role.edit') && !$role->system_reserve;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('role.destroy') && !$role->system_reserve;
    }
}
