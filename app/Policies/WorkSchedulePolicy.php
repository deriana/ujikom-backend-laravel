<?php

namespace App\Policies;

use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class WorkSchedulePolicy
 *
 * Menangani logika otorisasi untuk operasi pada model WorkSchedule (Jadwal Kerja).
 */
class WorkSchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar jadwal kerja.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('work-schedule.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail jadwal kerja tertentu.
     *
     * @param User $user
     * @param WorkSchedule $workSchedule
     * @return bool
     */
    public function view(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data jadwal kerja baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('work-schedule.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data jadwal kerja.
     *
     * @param User $user
     * @param WorkSchedule $workSchedule
     * @return bool
     */
    public function update(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.edit') && ! $workSchedule->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jadwal kerja.
     *
     * @param User $user
     * @param WorkSchedule $workSchedule
     * @return bool
     */
    public function delete(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.destroy') && ! $workSchedule->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan data jadwal kerja yang dihapus lunak.
     *
     * @param User $user
     * @param WorkSchedule $workSchedule
     * @return bool
     */
    public function restore(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.restore');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data jadwal kerja secara permanen.
     *
     * @param User $user
     * @param WorkSchedule $workSchedule
     * @return bool
     */
    public function forceDelete(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.forceDelete');
    }
}
