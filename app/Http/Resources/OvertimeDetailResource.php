<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class OvertimeDetailResource
 *
 * Resource class untuk mentransformasi detail model Overtime menjadi format JSON yang mendalam,
 * mencakup data karyawan, detail absensi terkait, durasi, dan informasi persetujuan.
 */
class OvertimeDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array untuk tampilan detail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi detail pengajuan lembur yang komprehensif.
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan lembur */
            'employee' => [
                'name' => $this->employee->user->name ?? null, /**< Nama karyawan yang mengajukan */
                'nik' => $this->employee->nik ?? null, /**< NIK karyawan */
                'team' => $this->employee->team?->name, /**< Nama tim karyawan */
                'division' => $this->employee->team?->division?->name, /**< Nama divisi karyawan */
                'position' => $this->employee->position?->name, /**< Nama jabatan karyawan */
            ],
            'attendance' => [
                'date' => $this->attendance?->date?->format('Y-m-d'), /**< Tanggal absensi terkait lembur */
                'clock_in' => $this->attendance?->clock_in?->format('H:i:s'), /**< Waktu masuk aktual */
                'clock_out' => $this->attendance?->clock_out?->format('H:i:s'), /**< Waktu keluar aktual */
            ],
            'duration_minutes' => $this->duration_minutes, /**< Durasi lembur dalam menit */
            'reason' => $this->reason, /**< Alasan pengajuan lembur */
            'status' => $this->status, /**< Status persetujuan (enum value) */
            'status_label' => $this->getStatusLabel(), /**< Label status yang mudah dibaca */
            'approved_by' => [
                'name' => $this->manager->user->name ?? null, /**< Nama manajer yang menyetujui */
            ],
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'), /**< Waktu persetujuan diberikan */
            'note' => $this->note, /**< Catatan dari pemberi persetujuan */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan pengajuan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }

    /**
     * Mendapatkan label string untuk status persetujuan.
     *
     * @return string Label status (Pending, Approved, Rejected, atau Unknown)
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            ApprovalStatus::PENDING->value => 'Pending',
            ApprovalStatus::APPROVED->value => 'Approved',
            ApprovalStatus::REJECTED->value => 'Rejected',
            default => 'Unknown', /**< Status tidak dikenali */
        };
    }
}
