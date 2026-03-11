<?php

namespace App\Policies;

use App\Models\Allowance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class AllowancePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Allowance.
 */
class AllowancePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar tunjangan.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('allowance.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail tunjangan tertentu.
     *
     * @param User $user
     * @param Allowance $allowance
     * @return bool
     */
    public function view(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data tunjangan baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('allowance.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data tunjangan.
     *
     * @param User $user
     * @param Allowance $allowance
     * @return bool
     */
    public function update(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.edit') && !$allowance->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data tunjangan.
     *
     * @param User $user
     * @param Allowance $allowance
     * @return bool
     */
    public function delete(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.destroy') && !$allowance->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data tunjangan yang dihapus lunak.
     *
     * @param User $user
     * @param Allowance $allowance
     * @return bool
     */
    public function restore(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.restore');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data tunjangan secara permanen.
     *
     * @param User $user
     * @param Allowance $allowance
     * @return bool
     */
    public function forceDelete(User $user, Allowance $allowance): bool
    {
        return $user->can('allowance.forceDelete');
    }
}
