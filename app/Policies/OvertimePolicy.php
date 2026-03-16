<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class OvertimePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Overtime (Lembur).
 */
class OvertimePolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah pengguna adalah pemilik data (karyawan terkait)
     * atau memiliki peran staf (Admin/HR).
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    private function isOwnerOrStaff(User $user, Overtime $overtime): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $overtime->employee_id === $userEmployeeId;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengajuan lembur.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('overtime.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengajuan lembur tertentu.
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    public function view(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan lembur baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('overtime.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengajuan lembur.
     *
     * Pengubahan hanya diizinkan jika data bukan cadangan sistem,
     * status masih pending, dan pengguna adalah pemilik atau staf.
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    public function update(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.edit') &&
               !$overtime->system_reserve &&
               $overtime->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $overtime);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengajuan lembur.
     *
     * Penghapusan hanya diizinkan jika data bukan cadangan sistem,
     * status masih pending, dan pengguna adalah pemilik atau staf.
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    public function delete(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.destroy') &&
               !$overtime->system_reserve &&
               $overtime->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $overtime);
    }

    /**
     * Menentukan apakah pengguna memiliki izin untuk menyetujui/menolak pengajuan lembur.
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    public function approve(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.approve');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data pengajuan lembur.
     *
     * @param User $user
     * @param Overtime $overtime
     * @return bool
     */
    public function export(User $user, Overtime $overtime): bool
    {
        return $user->can('overtime.export');
    }
}
