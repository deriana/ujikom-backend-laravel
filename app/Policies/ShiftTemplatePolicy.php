<?php

namespace App\Policies;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('shift-template.index');
    }

    public function view(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.show');
    }

    public function create(User $user): bool
    {
        return $user->can('shift-template.create');
    }

    public function update(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.edit') && ! $shiftTemplate->system_reserve;
    }

    public function delete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.destroy') && ! $shiftTemplate->system_reserve;
    }

    public function restore(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.restore');
    }

    public function forceDelete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.forceDelete');
    }
}
