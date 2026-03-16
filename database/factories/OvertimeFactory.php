<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OvertimeFactory extends Factory
{
    protected $model = Overtime::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'attendance_id' => Attendance::factory(),
            'employee_id' => Employee::factory(),
            'duration_minutes' => $this->faker->numberBetween(60, 240), // 1-4 jam
            'reason' => $this->faker->sentence(),
            'status' => ApprovalStatus::PENDING->value,
            'approved_by_id' => null,
            'approved_at' => null,
            'note' => null,
        ];
    }

    public function approved()
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::APPROVED->value,
            'approved_by_id' => Employee::factory(),
            'approved_at' => now(),
            'note' => 'Disetujui untuk lembur proyek.',
        ]);
    }
}