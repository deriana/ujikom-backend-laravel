<?php

namespace App\Policies;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HolidayPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('holiday.index');
    }

    public function create(User $user): bool
    {
        return $user->can('holiday.create');
    }

    public function update(User $user, Holiday $holiday): bool
    {
        return $user->can('holiday.edit') && ! $holiday->system_reserve;
    }

    public function delete(User $user, Holiday $holiday): bool
    {
        return $user->can('holiday.destroy') && ! $holiday->system_reserve;
    }
}
