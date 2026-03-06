<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailService
{
    /**
     * Get a list of attendance records based on user roles and filters.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(array $filters = [])
    {
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        $query = Attendance::query()->with('employee.user');

        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value
        ])) {
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee', function ($q) use ($currentUserEmployee) {
                $q->where('id', $currentUserEmployee->id)
                    ->orWhere('manager_id', $currentUserEmployee->id);
            });
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value) || $user->hasRole(UserRole::FINANCE->value)) {
            $query->where('employee_id', $currentUserEmployee->id);
        } else {
            return collect([]);
        }

        // --- FILTER DATE RANGE (Logika kamu sebelumnya) ---
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        } else {
            $query->whereDate('date', Carbon::today());
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get the details of a specific attendance record.
     *
     * @param Attendance $attendance
     * @return Attendance
     */
    public function show(Attendance $attendance)
    {
        return $attendance->load('employee');
    }
}
