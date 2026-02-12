<?php

namespace App\Policies;

use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('work-schedule.index');
    }

    public function view(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.show');
    }

    public function create(User $user): bool
    {
        return $user->can('work-schedule.create');
    }

    public function update(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.edit') && ! $workSchedule->system_reserve;
    }

    public function delete(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.destroy') && ! $workSchedule->system_reserve;
    }

    public function restore(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.restore');
    }

    public function forceDelete(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->can('work-schedule.forceDelete');
    }
}
