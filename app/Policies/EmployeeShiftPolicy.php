<?php

namespace App\Policies;

use App\Models\EmployeeShift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class EmployeeShiftPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model EmployeeShift.
 */
class EmployeeShiftPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar penugasan shift karyawan.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('employee-shift.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail penugasan shift tertentu.
     *
     * @param User $user
     * @param EmployeeShift $employeeShift
     * @return bool
     */
    public function view(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat penugasan shift baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('employee-shift.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data penugasan shift.
     *
     * @param User $user
     * @param EmployeeShift $employeeShift
     * @return bool
     */
    public function update(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.edit') && ! $employeeShift->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data penugasan shift.
     *
     * @param User $user
     * @param EmployeeShift $employeeShift
     * @return bool
     */
    public function delete(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.destroy') && ! $employeeShift->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data penugasan shift.
     *
     * @param User $user
     * @param EmployeeShift $employeeShift
     * @return bool
     */
    public function export(User $user, EmployeeShift $employeeShift): bool
    {
        return $user->can('employee-shift.export');
    }
}
