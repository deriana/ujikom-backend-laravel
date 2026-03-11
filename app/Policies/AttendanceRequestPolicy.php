<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
/**
 * Class AttendanceRequestPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model AttendanceRequest.
 */
class AttendanceRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Helper untuk mengecek apakah pengguna adalah pemilik data (karyawan terkait)
     * atau memiliki peran staf (Admin/HR).
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    private function isOwnerOrStaff(User $user, AttendanceRequest $attendanceRequest): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $attendanceRequest->employee_id === $userEmployeeId;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar pengajuan absensi.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('attendance-request.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail pengajuan absensi tertentu.
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    public function view(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan absensi baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('attendance-request.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data pengajuan absensi.
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    public function update(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.edit') &&
               ! $attendanceRequest->system_reserve &&
               $attendanceRequest->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceRequest);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengajuan absensi.
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    public function delete(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.destroy') &&
               ! $attendanceRequest->system_reserve &&
               $attendanceRequest->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceRequest);
    }

    /**
     * Menentukan apakah pengguna memiliki izin untuk menyetujui/menolak pengajuan absensi.
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    public function approve(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.approve');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data pengajuan absensi.
     *
     * @param User $user
     * @param AttendanceRequest $attendanceRequest
     * @return bool
     */
    public function export(User $user, AttendanceRequest $attendanceRequest): bool
    {
        return $user->can('attendance-request.export');
    }
}
