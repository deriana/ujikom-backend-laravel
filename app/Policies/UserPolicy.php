<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('user.index');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('user.show') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('user.edit') || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('user.destroy');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('user.restore') && $user->id === $model->created_by_id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('user.forceDelete') && $user->id === $model->created_by_id;
    }

    public function terminated(User $user, User $model): bool
    {
        return $user->can('user.terminated');
    }

    public function changePassword(User $user, User $model): bool
    {
        return $user->can('user.change_password') || $user->id === $model->id;
    }
}
