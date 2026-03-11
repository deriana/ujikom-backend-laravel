<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class SettingPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Setting (Pengaturan).
 */
class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengaturan.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('setting.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengaturan tertentu.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->can('setting.index');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengaturan baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengaturan.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->can('setting.edit');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengaturan.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function delete(User $user, Setting $setting): bool
    {
        return false;
    }
}
