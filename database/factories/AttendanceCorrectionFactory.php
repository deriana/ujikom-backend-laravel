<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'employee_id'   => Employee::factory(),
            'clock_in_requested'  => '08:00:00',
            'clock_out_requested' => '17:00:00',
            'reason'     => $this->faker->sentence(),
            'status'     => 0, // Pending
            'attachment' => $this->faker->imageUrl(),
            'note'       => null,
            // uuid tidak perlu diisi karena ada di boot() model
        ];
    }
}
