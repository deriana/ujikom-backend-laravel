<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EarlyLeave>
 */
class EarlyLeaveFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     *
     * @var string
     */
    protected $model = EarlyLeave::class;

    /**
     * Mendefinisikan status default untuk model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'attendance_id' => Attendance::factory(), // Otomatis buat data presensi terkait
            'employee_id' => Employee::factory(),   // Otomatis buat data karyawan terkait
            'minutes_early' => $this->faker->numberBetween(30, 120), // Pulang awal antara 30-120 menit
            'reason' => $this->faker->sentence(),
            'attachment' => 'attachments/sample-proof.jpg',
            'status' => ApprovalStatus::PENDING->value,
            'approved_by_id' => null,
            'approved_at' => null,
            'note' => null,
        ];
    }

    /**
     * State untuk pengajuan yang sudah disetujui.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApprovalStatus::APPROVED->value,
            'approved_by_id' => Employee::factory(),
            'approved_at' => now(),
            'note' => 'Izin disetujui, jaga kesehatan.',
        ]);
    }

    /**
     * State untuk pengajuan yang ditolak.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApprovalStatus::REJECTED->value,
            'approved_by_id' => Employee::factory(),
            'approved_at' => now(),
            'note' => 'Alasan kurang kuat.',
        ]);
    }
}
