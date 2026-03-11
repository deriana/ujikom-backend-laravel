<?php

namespace App\Policies;

use App\Models\EarlyLeave;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class EarlyLeavePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model EarlyLeave.
 */
class EarlyLeavePolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah pengguna adalah pemilik data (karyawan terkait)
     * atau memiliki peran staf (Admin/HR).
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    private function isOwnerOrStaff(User $user, EarlyLeave $earlyLeave): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $earlyLeave->employee_id === $userEmployeeId;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengajuan pulang awal.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('early-leave.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengajuan pulang awal tertentu.
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    public function view(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan pulang awal baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('early-leave.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengajuan pulang awal.
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    public function update(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.edit') &&
               !$earlyLeave->system_reserve &&
               $earlyLeave->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $earlyLeave);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengajuan pulang awal.
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    public function delete(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.destroy') &&
               !$earlyLeave->system_reserve &&
               $earlyLeave->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $earlyLeave);
    }

    /**
     * Menentukan apakah pengguna memiliki izin untuk menyetujui/menolak pengajuan pulang awal.
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    public function approve(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.approve');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data pengajuan pulang awal.
     *
     * @param User $user
     * @param EarlyLeave $earlyLeave
     * @return bool
     */
    public function export(User $user, EarlyLeave $earlyLeave): bool
    {
        return $user->can('early-leave.export');
    }
}
