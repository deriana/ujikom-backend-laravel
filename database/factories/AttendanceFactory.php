<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'work_minutes' => 480,
            'overtime_minutes' => 0,
            'clock_in_photo' => 'photos/in.jpg',
            'clock_out_photo' => 'photos/out.jpg',
            'latitude_in' => -6.200,
            'longitude_in' => 106.816,
            'latitude_out' => -6.200,
            'longitude_out' => 106.816,
            'is_early_leave_approved' => false,
            'is_corrected' => false,
        ];
    }
}
