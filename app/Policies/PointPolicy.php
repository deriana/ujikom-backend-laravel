<?php

namespace App\Policies;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PointPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Point (PointTransaction).
 */
class PointPolicy
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
        return $user->can('point.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail transaksi poin tertentu.
     *
     * @param User $user
     * @param PointTransaction $pointTransaction
     * @return bool
     */
    public function view(User $user, PointTransaction $pointTransaction): bool
    {
        return $user->can('point.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data transaksi poin baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('point.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data transaksi poin.
     *
     * @param User $user
     * @param PointTransaction $pointTransaction
     * @return bool
     */
    public function update(User $user, PointTransaction $pointTransaction): bool
    {
        return $user->can('point.edit');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data transaksi poin.
     *
     * @param User $user
     * @param PointTransaction $pointTransaction
     * @return bool
     */
    public function delete(User $user, PointTransaction $pointTransaction): bool
    {
        return $user->can('point.destroy');
    }

    public function export(User $user): bool
    {
        return $user->can('point.export');
    }
}
