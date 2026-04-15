<?php

namespace App\Policies;

use App\Models\PointRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PointRulePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Point (PointRule).
 */
class PointRulePolicy
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
        return $user->can('point-rule.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail transaksi poin tertentu.
     *
     * @param User $user
     * @param PointRule $pointRule
     * @return bool
     */
    public function view(User $user, PointRule $pointRule): bool
    {
        return $user->can('point-rule.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data transaksi poin baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('point-rule.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data transaksi poin.
     *
     * @param User $user
     * @param PointRule $pointRule
     * @return bool
     */
    public function update(User $user, PointRule $pointRule): bool
    {
        return $user->can('point-rule.edit');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data transaksi poin.
     *
     * @param User $user
     * @param PointRule $pointRule
     * @return bool
     */
    public function delete(User $user, PointRule $pointRule): bool
    {
        return $user->can('point-rule.destroy');
    }

    public function export(User $user): bool
    {
        return $user->can('point-rule.export');
    }
}
