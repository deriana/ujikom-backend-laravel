<?php

namespace App\Policies;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class HolidayPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Holiday.
 */
class HolidayPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar hari libur.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('holiday.index');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data hari libur baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('holiday.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data hari libur.
     *
     * @param User $user
     * @param Holiday $holiday
     * @return bool
     */
    public function update(User $user, Holiday $holiday): bool
    {
        return $user->can('holiday.edit') && ! $holiday->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data hari libur.
     *
     * @param User $user
     * @param Holiday $holiday
     * @return bool
     */
    public function delete(User $user, Holiday $holiday): bool
    {
        return $user->can('holiday.destroy') && ! $holiday->system_reserve;
    }
}
