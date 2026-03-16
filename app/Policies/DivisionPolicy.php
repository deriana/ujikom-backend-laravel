<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class DivisionPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Division.
 */
class DivisionPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar divisi.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('division.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail divisi tertentu.
     *
     * @param User $user
     * @param Division $division
     * @return bool
     */
    public function view(User $user, Division $division): bool
    {
        return $user->can('division.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data divisi baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('division.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data divisi.
     *
     * @param User $user
     * @param Division $division
     * @return bool
     */
    public function update(User $user, Division $division): bool
    {
        return $user->can('division.edit') && !$division->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data divisi.
     *
     * @param User $user
     * @param Division $division
     * @return bool
     */
    public function delete(User $user, Division $division): bool
    {
        return $user->can('division.destroy') && !$division->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data divisi yang dihapus lunak.
     *
     * @param User $user
     * @param Division $division
     * @return bool
     */
    public function restore(User $user, Division $division): bool
    {
        return $user->can('division.restore');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data divisi secara permanen.
     *
     * @param User $user
     * @param Division $division
     * @return bool
     */
    public function forceDelete(User $user, Division $division): bool
    {
        return $user->can('division.forceDelete');
    }
}
