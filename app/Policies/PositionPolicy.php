<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PositionPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Position (Jabatan).
 */
class PositionPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar jabatan.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('position.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail jabatan tertentu.
     *
     * @param User $user
     * @param Position $position
     * @return bool
     */
    public function view(User $user, Position $position): bool
    {
        return $user->can('position.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data jabatan baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('position.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data jabatan.
     *
     * @param User $user
     * @param Position $position
     * @return bool
     */
    public function update(User $user, Position $position): bool
    {
        return $user->can('position.edit') && ! $position->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jabatan.
     *
     * @param User $user
     * @param Position $position
     * @return bool
     */
    public function delete(User $user, Position $position): bool
    {
        return $user->can('position.destroy') && ! $position->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data jabatan yang dihapus lunak.
     *
     * @param User $user
     * @param Position $position
     * @return bool
     */
    public function restore(User $user, Position $position): bool
    {
        return $user->can('position.restore');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jabatan secara permanen.
     *
     * @param User $user
     * @param Position $position
     * @return bool
     */
    public function forceDelete(User $user, Position $position): bool
    {
        return $user->can('position.forceDelete');
    }
}
