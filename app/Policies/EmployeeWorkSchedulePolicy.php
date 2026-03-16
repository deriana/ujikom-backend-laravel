<?php

namespace App\Policies;

use App\Models\EmployeeWorkSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class EmployeeWorkSchedulePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model EmployeeWorkSchedule.
 */
class EmployeeWorkSchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar jadwal kerja karyawan.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('employee-work-schedule.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail jadwal kerja karyawan tertentu.
     *
     * @param User $user
     * @param EmployeeWorkSchedule $schedule
     * @return bool
     */
    public function view(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.show') || $user->id === $schedule->creator_id;
    }

    /**
     * Menentukan apakah pengguna dapat membuat data jadwal kerja karyawan baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('employee-work-schedule.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data jadwal kerja karyawan tertentu.
     *
     * @param User $user
     * @param EmployeeWorkSchedule $schedule
     * @return bool
     */
    public function update(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.edit') || $user->id === $schedule->creator_id;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jadwal kerja karyawan tertentu.
     *
     * @param User $user
     * @param EmployeeWorkSchedule $schedule
     * @return bool
     */
    public function delete(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.destroy') || $user->id === $schedule->creator_id;
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data jadwal kerja karyawan.
     *
     * @param User $user
     * @param EmployeeWorkSchedule $schedule
     * @return bool
     */
    public function export(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.export');
    }
}
