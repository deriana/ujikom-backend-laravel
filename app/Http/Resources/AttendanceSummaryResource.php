<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee' => [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'name' => $this->employee->user->name,
                'position' => $this->employee->position?->name,
                'team' => $this->employee->team?->name,
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
            ],
            'attendance_stats' => [
                'total_days' => (int) $this->total_days,
                'total_present' => (int) $this->total_present,
                'on_time' => (int) $this->on_time_count,
                'late' => [
                    'count' => (int) $this->late_count,
                    'total_minutes' => (int) $this->total_late_minutes,
                    'formatted' => $this->formatMinutes($this->total_late_minutes),
                ],
                'early_leave' => [
                    'count' => (int) $this->early_leave_count,
                    'total_minutes' => (int) $this->total_early_leave_minutes,
                    'formatted' => $this->formatMinutes($this->total_early_leave_minutes),
                ],
                'absent' => (int) $this->absent_count,
                'leave' => [
                    'total_days' => (float) $this->total_leave_days,
                    'details' => $this->leave_details,
                ],
            ],
            'work_stats' => [
                'work_time' => [
                    'total_minutes' => (int) $this->total_work_minutes,
                    'formatted' => $this->formatMinutes($this->total_work_minutes),
                ],
                'overtime' => [
                    'total_minutes' => (int) $this->total_overtime_minutes,
                    'formatted' => $this->formatMinutes($this->total_overtime_minutes),
                ],
            ],
        ];
    }

    /**
     * Helper untuk mengubah menit ke format Jam & Menit
     */
    private function formatMinutes($minutes)
    {
        if ($minutes <= 0) return "0h 0m";

        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;

        return "{$hours}h {$remMinutes}m";
    }
}
