<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PayrollPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Payroll (Penggajian).
 */
class PayrollPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar penggajian.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail penggajian tertentu.
     *
     * @param User $user
     * @param Payroll $payroll
     * @return bool
     */
    public function view(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data penggajian baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('payroll.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data penggajian.
     *
     * Pengubahan hanya diizinkan jika status masih Draft (0).
     *
     * @param User $user
     * @param Payroll $payroll
     * @return bool
     */
    public function update(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.edit') && $payroll->status === 0; // Draft
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data penggajian.
     *
     * @param User $user
     * @param Payroll $payroll
     * @return bool
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.destroy');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data penggajian.
     *
     * @param User $user
     * @param Payroll $payroll
     * @return bool
     */
    public function export(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.export');
    }

    /**
     * Menentukan apakah pengguna dapat memproses pembayaran (pay) penggajian.
     *
     * @param User $user
     * @param Payroll $payroll
     * @return bool
     */
    public function pay(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.pay') && $payroll->status === 0;
    }
}
