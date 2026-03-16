<?php

namespace App\Policies;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
/**
 * Class ShiftTemplatePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model ShiftTemplate (Templat Shift).
 */
class ShiftTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar templat shift.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shift-template.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail templat shift tertentu.
     *
     * @param User $user
     * @param ShiftTemplate $shiftTemplate
     * @return bool
     */
    public function view(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat templat shift baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('shift-template.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data templat shift.
     *
     * @param User $user
     * @param ShiftTemplate $shiftTemplate
     * @return bool
     */
    public function update(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.edit') && ! $shiftTemplate->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data templat shift.
     *
     * @param User $user
     * @param ShiftTemplate $shiftTemplate
     * @return bool
     */
    public function delete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.destroy') && ! $shiftTemplate->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data templat shift yang dihapus lunak.
     *
     * @param User $user
     * @param ShiftTemplate $shiftTemplate
     * @return bool
     */
    public function restore(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.restore');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data templat shift secara permanen.
     *
     * @param User $user
     * @param ShiftTemplate $shiftTemplate
     * @return bool
     */
    public function forceDelete(User $user, ShiftTemplate $shiftTemplate): bool
    {
        return $user->can('shift-template.forceDelete');
    }
}
