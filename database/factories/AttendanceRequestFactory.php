<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\AttendanceRequest;
use App\Models\ShiftTemplate;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition(): array
    {
        // Tentukan tipe secara acak untuk data dummy
        $requestType = $this->faker->randomElement(['SHIFT', 'WORK_MODE']);

        return [
            'employee_id' => Employee::factory(),
            'request_type' => $requestType,

            // Logic: Jika tipe A, buat factory A. Jika tidak, null.
            'shift_template_id' => $requestType === 'SHIFT'
                ? ShiftTemplate::factory()
                : null,

            'work_schedules_id' => $requestType === 'WORK_MODE'
                ? WorkSchedule::factory()
                : null,

            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => $this->faker->sentence(),
            'status' => 0, // Default: Pending
            'approved_by_id' => null,
            'note' => null,
        ];
    }

    /**
     * State khusus untuk pengajuan yang sudah disetujui (Approved)
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'approved_by_id' => Employee::factory(), // Approver adalah Employee
            'note' => 'Disetujui oleh atasan.',
            'approved_at' => now(),
        ]);
    }

    /**
     * State khusus untuk pengajuan yang ditolak (Rejected)
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2,
            'approved_by_id' => Employee::factory(),
            'note' => 'Alasan penolakan: Dokumen tidak lengkap.',
            'approved_at' => now(),
        ]);
    }
}
