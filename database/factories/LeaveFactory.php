<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $isHalfDay = $this->faker->boolean(20); // 20% kemungkinan cuti setengah hari
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');

        // Jika half day, tanggal mulai dan selesai sama. Jika tidak, tambah 1-5 hari.
        $endDate = $isHalfDay
            ? $startDate
            : (clone $startDate)->modify('+' . rand(1, 5) . ' days');

        return [
            'uuid' => (string) Str::uuid(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'date_start' => $startDate->format('Y-m-d'),
            'date_end' => $endDate->format('Y-m-d'),
            'reason' => $this->faker->sentence(),
            'attachment' => null,
            'approval_status' => ApprovalStatus::PENDING->value,
            'is_half_day' => $isHalfDay,
        ];
    }

    /**
     * State untuk pengajuan yang sudah disetujui.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ApprovalStatus::APPROVED->value,
        ]);
    }

    /**
     * State untuk pengajuan yang ditolak.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ApprovalStatus::REJECTED->value,
        ]);
    }

    /**
     * State khusus untuk cuti setengah hari.
     */
    public function halfDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_half_day' => true,
            'date_end' => $attributes['date_start'],
        ]);
    }
}
