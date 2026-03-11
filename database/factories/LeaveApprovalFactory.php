<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApproval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveApprovalFactory extends Factory
{
    protected $model = LeaveApproval::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'leave_id' => Leave::factory(),
            'approver_id' => Employee::factory(),
            'level' => 0, // Default level 0 (Manager)
            'status' => ApprovalStatus::PENDING->value,
            'approved_at' => null,
            'note' => null,
        ];
    }

    /**
     * State untuk persetujuan level HR (Level 1).
     */
    public function hrLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 1,
        ]);
    }

    /**
     * State untuk status Approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApprovalStatus::APPROVED->value,
            'approved_at' => now(),
            'note' => 'Approved by system factory.',
        ]);
    }

    /**
     * State untuk status Rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApprovalStatus::REJECTED->value,
            'approved_at' => now(),
            'note' => 'Rejected by system factory.',
        ]);
    }
}
