<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('payroll.index');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.show');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.create');
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.edit') && $payroll->status === 0; // Draft
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.destroy');
    }

    public function export(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.export');
    }

    public function pay(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.pay') && $payroll->status === 0;
    }
}
