<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class AttendanceCorrectionPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model AttendanceCorrection.
 */
class AttendanceCorrectionPolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah pengguna adalah pemilik data (karyawan terkait)
     * atau memiliki peran staf (Admin/HR).
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    private function isOwnerOrStaff(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $attendanceCorrection->employee_id === $userEmployeeId;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengajuan koreksi absensi.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('attendance-correction.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail koreksi absensi tertentu.
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    public function view(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan koreksi absensi baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('attendance-correction.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data koreksi absensi.
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    public function update(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.edit') &&
               !$attendanceCorrection->system_reserve &&
               $attendanceCorrection->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceCorrection);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data koreksi absensi.
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    public function delete(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.destroy') &&
               !$attendanceCorrection->system_reserve &&
               $attendanceCorrection->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceCorrection);
    }

    /**
     * Menentukan apakah pengguna memiliki izin untuk menyetujui/menolak koreksi absensi.
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    public function approve(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.approve');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data koreksi absensi.
     *
     * @param User $user
     * @param AttendanceCorrection $attendanceCorrection
     * @return bool
     */
    public function export(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.export');
    }
}
