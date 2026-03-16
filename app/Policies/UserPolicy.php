<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
/**
 * Class UserPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model User (Pengguna).
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengguna.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('user.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengguna tertentu.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('user.show') || $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengguna baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengguna.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('user.edit') || $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengguna.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('user.destroy');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data pengguna yang dihapus lunak.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('user.restore') && $user->id === $model->created_by_id;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengguna secara permanen.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('user.forceDelete') && $user->id === $model->created_by_id;
    }

    /**
     * Menentukan apakah pengguna dapat menonaktifkan (terminate) pengguna.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function terminated(User $user, User $model): bool
    {
        return $user->can('user.terminated');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah kata sandi pengguna.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function changePassword(User $user, User $model): bool
    {
        return $user->can('user.change_password') || $user->id === $model->id;
    }
}
