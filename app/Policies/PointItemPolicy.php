<?php

namespace App\Policies;

use App\Models\PointItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PointItemPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Point (PointItem).
 */
class PointItemPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar transaksi poin.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('point-item.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail transaksi poin tertentu.
     *
     * @param User $user
     * @param PointItem $pointItem
     * @return bool
     */
    public function view(User $user, PointItem $pointItem): bool
    {
        return $user->can('point-item.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data transaksi poin baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('point-item.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data transaksi poin.
     *
     * @param User $user
     * @param PointItem $pointItem
     * @return bool
     */
    public function update(User $user, PointItem $pointItem): bool
    {
        return $user->can('point-item.edit');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data transaksi poin.
     *
     * @param User $user
     * @param PointItem $pointItem
     * @return bool
     */
    public function delete(User $user, PointItem $pointItem): bool
    {
        return $user->can('point-item.destroy');
    }

    public function export(User $user): bool
    {
        return $user->can('point-item.export');
    }
}
