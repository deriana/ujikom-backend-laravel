<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
     * @param array $filters Filter rentang tanggal (start_date, end_date).
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
     * @param Attendance $attendance Objek kehadiran.
     * @return Attendance
     */
    public function show(Attendance $attendance)
    {
        return $attendance->load('employee');
    }
}
