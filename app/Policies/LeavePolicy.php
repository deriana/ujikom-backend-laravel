<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
/**
 * Class LeavePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Leave.
 */
class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah pengguna adalah pemilik data (karyawan terkait)
     * atau memiliki peran staf (Admin/HR).
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    private function isOwnerOrStaff(User $user, Leave $leave): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $leave->employee_id === $userEmployeeId;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengajuan cuti.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('leave.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengajuan cuti tertentu.
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    public function view(User $user, Leave $leave): bool
    {
        return $user->can('leave.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan cuti baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('leave.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengajuan cuti.
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    public function update(User $user, Leave $leave): bool
    {
        return $user->can('leave.edit') &&
               !$leave->system_reserve &&
               $leave->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $leave);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengajuan cuti.
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    public function delete(User $user, Leave $leave): bool
    {
        return $user->can('leave.destroy') &&
            !$leave->system_reserve &&
            // Ganti dari $leave->status menjadi $leave->approval_status
            $leave->approval_status === ApprovalStatus::PENDING->value &&
            $this->isOwnerOrStaff($user, $leave);
    }

    /**
     * Menentukan apakah pengguna memiliki izin untuk menyetujui/menolak pengajuan cuti.
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    public function approve(User $user, Leave $leave): bool
    {
        return $user->can('leave.approve');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data pengajuan cuti.
     *
     * @param User $user
     * @param Leave $leave
     * @return bool
     */
    public function export(User $user, Leave $leave): bool
    {
        return $user->can('leave.export');
    }
}
