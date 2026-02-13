<?php

namespace App\Policies;

use App\Models\EmployeeShift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('employee-shift.index');
    }

    public function view(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.show');
    }

    public function create(User $user): bool
    {
        return $user->can('employee-shift.create');
    }

    public function update(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.edit') && ! $employeeShift->system_reserve;
    }

    public function delete(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.destroy') && ! $employeeShift->system_reserve;
    }
}
