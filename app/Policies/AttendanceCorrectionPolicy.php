<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ApprovalStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceCorrectionPolicy
{
    use HandlesAuthorization;

    private function isOwnerOrStaff(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        $userEmployeeId = optional($user->employee)->id;

        return $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) ||
               $attendanceCorrection->employee_id === $userEmployeeId;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('attendance-correction.index');
    }

    public function view(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.show');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance-correction.create');
    }

    public function update(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.edit') &&
               !$attendanceCorrection->system_reserve &&
               $attendanceCorrection->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceCorrection);
    }

    public function delete(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.destroy') &&
               !$attendanceCorrection->system_reserve &&
               $attendanceCorrection->status === ApprovalStatus::PENDING->value &&
               $this->isOwnerOrStaff($user, $attendanceCorrection);
    }

    public function approve(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.approve');
    }

    public function export(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance-correction.export');
    }
}
