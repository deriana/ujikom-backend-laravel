<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class AttendancePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Attendance.
 */
class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar absensi.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail absensi tertentu.
     *
     * @param User $user
     * @param Attendance $attendance
     * @return bool
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.show');
    }

    /**
     * Menentukan apakah pengguna dapat melakukan sinkronisasi data absensi.
     *
     * @param User $user
     * @return bool
     */
    public function sync(User $user): bool
    {
        return $user->can('attendance.sync');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data absensi.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $user->can('attendance.export');
    }
}
