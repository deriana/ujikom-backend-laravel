<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('setting.index');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('setting.index');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('setting.edit');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return false;
    }
}
