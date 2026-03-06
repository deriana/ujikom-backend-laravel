<?php

namespace App\Policies;

use App\Models\EmployeeWorkSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeWorkSchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any employee work schedules.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('employee-work-schedule.index');
    }

    /**
     * Determine whether the user can view a specific employee work schedule.
     */
    public function view(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.show') || $user->id === $schedule->creator_id;
    }

    /**
     * Determine whether the user can create employee work schedules.
     */
    public function create(User $user): bool
    {
        return $user->can('employee-work-schedule.create');
    }

    /**
     * Determine whether the user can update a specific employee work schedule.
     */
    public function update(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.edit') || $user->id === $schedule->creator_id;
    }

    /**
     * Determine whether the user can delete a specific employee work schedule.
     */
    public function delete(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.destroy') || $user->id === $schedule->creator_id;
    }

    public function export(User $user, EmployeeWorkSchedule $schedule): bool
    {
        return $user->can('employee-work-schedule.export');
    }
}
