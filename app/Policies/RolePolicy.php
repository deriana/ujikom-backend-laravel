<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class RolePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Role (Peran).
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar peran.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('role.index') || $user->hasRole(UserRole::HR);
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail peran tertentu.
     *
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('role.index');
    }

    /**
     * Menentukan apakah pengguna dapat membuat peran baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('role.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data peran.
     *
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('role.edit') && !$role->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data peran.
     *
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('role.destroy') && !$role->system_reserve;
    }
}
