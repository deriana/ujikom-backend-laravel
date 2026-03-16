<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class OvertimeResource
 *
 * Resource class untuk mentransformasi model Overtime menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class OvertimeResource extends JsonResource
{
    /**
     * Transform resource ke dalam array untuk tampilan tabel/list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data pengajuan lembur beserta status dan izin aksi.
     */
    public function toArray($request): array
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($myEmployeeId && $requesterManagerId == $myEmployeeId)
                      && ($this->status === ApprovalStatus::PENDING->value);

        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan lembur */
            'employee_name' => $this->employee->user->name ?? null, /**< Nama karyawan yang mengajukan */
            'employee_nik' => $this->employee->nik ?? null, /**< NIK karyawan yang mengajukan */
            'date' => $this->attendance?->date?->format('Y-m-d'), /**< Tanggal absensi terkait lembur */
            'duration_minutes' => $this->duration_minutes, /**< Durasi lembur dalam menit */
            'reason' => $this->reason, /**< Alasan pengajuan lembur */
            'status' => $this->status, /**< Status persetujuan (enum value) */
            'status_label' => $this->getStatusLabel(), /**< Label status yang mudah dibaca */
            'approved_by' => $this->manager->name ?? null, /**< Nama manajer yang memberikan persetujuan */
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'), /**< Waktu persetujuan diberikan */
            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource),
            ],
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
            default => 'Unknown',
        };
    }
}
