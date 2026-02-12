<?php

namespace App\Policies;

use App\Models\EmployeeWorkSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeWorkSchedulePolicy
{

    public function viewAny(EmployeeWorkSchedule $employeeWorkSchedule): bool
    {
        return $employeeWorkSchedule->can('employee-work-schedule.index');
    }

    public function view(EmployeeWorkSchedule $employeeWorkSchedule, EmployeeWorkSchedule $model): bool
    {
        return $employeeWorkSchedule->can('employee-work-schedule.index') || $employeeWorkSchedule->id === $model->id;
    }

    public function create(EmployeeWorkSchedule $employeeWorkSchedule): bool
    {
        return $employeeWorkSchedule->can('employee-work-schedule.create');
    }

    public function update(EmployeeWorkSchedule $employeeWorkSchedule, EmployeeWorkSchedule $model): bool
    {
        return $employeeWorkSchedule->can('employee-work-schedule.edit');
    }

    public function delete(EmployeeWorkSchedule $employeeWorkSchedule, EmployeeWorkSchedule $model): bool
    {
        return $employeeWorkSchedule->can('employee-work-schedule.destroy');
    }
}
