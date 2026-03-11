<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class LeaveTypePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model LeaveType (Jenis Cuti).
 */
class LeaveTypePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar jenis cuti.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('leave_type.index');
    }

    /**
     * Menentukan apakah pengguna dapat membuat jenis cuti baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('leave_type.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data jenis cuti.
     *
     * @param User $user
     * @param LeaveType $leaveType
     * @return bool
     */
    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave_type.edit') && ! $leaveType->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jenis cuti.
     *
     * @param User $user
     * @param LeaveType $leaveType
     * @return bool
     */
    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->can('leave_type.destroy') && ! $leaveType->system_reserve;
    }
}
