<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('attendance.index');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.show');
    }

    public function sync(User $user): bool
    {
        return $user->can('attendance.sync');
    }

    public function export(User $user): bool
    {
        return $user->can('attendance.export');
    }
}
