<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class AttendanceDetailService
 *
 * Menangani pengambilan data detail kehadiran karyawan dengan filter berdasarkan peran pengguna.
 */
class AttendanceDetailService
{
    /**
     * Mengambil daftar catatan kehadiran berdasarkan peran pengguna dan filter.
     *
     * @param  array  $filters  Filter rentang tanggal (start_date, end_date).
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(array $filters = [])
    {
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        $query = Attendance::query()->select([
            'id', 'employee_id', 'date', 'status',
            'clock_in', 'clock_out', 'late_minutes',
            'work_minutes', 'overtime_minutes',
        ])->with([
            'employee:id,nik,user_id',
            'employee.user:id,name',
        ]);

        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value,
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

        // --- FILTER RENTANG TANGGAL ---
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
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
     * Mengambil detail lengkap dari satu catatan kehadiran tertentu.
     *
     * @param  Attendance  $attendance  Objek kehadiran.
     * @return Attendance
     */
    public function show(Attendance $attendance)
    {
        return $attendance->load('employee.user', 'employee.team.division', 'employee.position', 'attendanceCorrection');
    }

    /**
     * Mengambil log aktivitas kehadiran (clock in/out) untuk catatan tertentu.
     *
     * @param  Attendance  $attendance
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLogs(array $filters = [])
    {
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        $query = AttendanceLog::query()->with(['employee.user']);

        // --- ROLE BASED FILTERING ---
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value,
        ])) {
            // Full access
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee', function ($q) use ($currentUserEmployee) {
                $q->where('id', $currentUserEmployee->id)
                    ->orWhere('manager_id', $currentUserEmployee->id);
            });
        } else {
            $query->where('employee_id', $currentUserEmployee->id);
        }

        $date = ! empty($filters['date'])
            ? Carbon::parse($filters['date'])->toDateString()
            : Carbon::today()->toDateString();

        $query->whereDate('created_at', $date);

        return $query->latest()->get();
    }

    public function getSummary(array $filters = [])
    {
        $startDate = ! empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = ! empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : Carbon::now()->endOfMonth();

        $user = Auth::user();
        $query = Attendance::query();

        // --- 1. Role Filtering ---
        if ($user->hasRole(UserRole::MANAGER->value)) {
            $employee = $user->employee;
            if (! $employee) return collect();

            $query->whereHas('employee', function ($q) use ($employee) {
                $q->where('id', $employee->id)->orWhere('manager_id', $employee->id);
            });
        } elseif (! $user->hasAnyRole([UserRole::ADMIN->value, UserRole::DIRECTOR->value, UserRole::OWNER->value, UserRole::HR->value])) {
            $query->where('employee_id', $user->employee?->id);
        }

        $summary = $query->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                employee_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" AND late_minutes = 0 THEN 1 ELSE 0 END) as on_time_count,
                SUM(CASE WHEN late_minutes > 0 THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = "leave" THEN 1 ELSE 0 END) as total_leave_days,
                SUM(CASE WHEN early_leave_minutes > 0 THEN 1 ELSE 0 END) as early_leave_count,
                SUM(late_minutes) as total_late_minutes,
                SUM(work_minutes) as total_work_minutes,
                SUM(overtime_minutes) as total_overtime_minutes,
                SUM(early_leave_minutes) as total_early_leave_minutes
            ')
            ->groupBy('employee_id')
            ->with([
                'employee:id,nik,user_id,position_id,team_id,deleted_at',
                'employee.user:id,name',
                'employee.position:id,name',
                'employee.team:id,name',
                'employee.leaves' => function ($q) use ($startDate, $endDate) {
                    $q->approved()
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('date_start', [$startDate, $endDate])
                                ->orWhereBetween('date_end', [$startDate, $endDate]);
                    })
                    ->with('leaveType:id,name');
                },
                'employee.media',
            ])
            ->get();

        Log::info('Attendance Summary Raw Result:', ['count' => $summary->count(), 'data' => $summary->toArray()]);

        return $summary->map(function ($item) {
            $item->leave_details = $item->employee->leaves->groupBy('leaveType.name')
                ->map(function ($leaves) {
                    return $leaves->sum('duration');
                });

            $item->total_work_hours = round($item->total_work_minutes / 60, 2);
            $item->total_overtime_hours = round($item->total_overtime_minutes / 60, 2);

            unset($item->employee->leaves);

            return $item;
        });
    }
}
