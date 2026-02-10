<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceDetailService
{
    public function index(array $filters = [])
    {
        $query = Attendance::query()->with('employee');

        // Filter date range
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        } else {
            // Default hari ini
            $query->whereDate('date', Carbon::today());
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function show(Attendance $attendance)
    {
        return $attendance->load('employee');
    }
}
