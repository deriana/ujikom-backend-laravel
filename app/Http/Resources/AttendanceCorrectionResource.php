<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class AttendanceCorrectionResource
 *
 * Resource class untuk mentransformasi model AttendanceCorrection menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class AttendanceCorrectionResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data koreksi absensi termasuk perbandingan waktu aktual dan pengajuan.
     */
    public function toArray($request): array
    {
        $user = Auth::user();

        return [
            'uuid' => $this->uuid, /**< Identifier unik koreksi absensi */
            'employee_name' => $this->employee->user->name ?? null, /**< Nama karyawan yang mengajukan */
            'employee_nik' => $this->employee->nik ?? null, /**< NIK karyawan yang mengajukan */
            'attendance_id' => $this->attendance_id, /**< ID data presensi yang dikoreksi */
            'attendance_date' => $this->attendance?->date?->format('Y-m-d'), /**< Tanggal absensi yang dikoreksi */
            'actual_clock_in' => $this->attendance?->clock_in?->format('H:i'), /**< Waktu masuk aktual di sistem */
            'actual_clock_out' => $this->attendance?->clock_out?->format('H:i'), /**< Waktu keluar aktual di sistem */

            'clock_in_requested' => $this->clock_in_requested?->format('H:i'), /**< Waktu masuk yang diajukan */
            'clock_out_requested' => $this->clock_out_requested?->format('H:i'), /**< Waktu keluar yang diajukan */
            'reason' => $this->reason, /**< Alasan pengajuan koreksi */

            'attachment' => $this->attachment ? [
                'exists' => true, /**< Status keberadaan lampiran */
                'filename' => basename($this->attachment), /**< Nama file lampiran */
                'path' => $this->attachment, /**< Path file lampiran */
            ] : null,

            'status' => $this->status, /**< Status persetujuan (enum value) */
            'status_label' => $this->getStatusLabel(), /**< Label status yang mudah dibaca */
            'note' => $this->note, /**< Catatan dari pemberi persetujuan */
            'approver_name' => $this->approver?->user?->name ?? null, /**< Nama pemberi persetujuan */
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'), /**< Waktu persetujuan diberikan */

            'can' => [/**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource) && $this->status === ApprovalStatus::PENDING->value,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i'), /**< Waktu pembuatan pengajuan */
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
