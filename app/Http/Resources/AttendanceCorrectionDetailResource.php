<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AttendanceCorrectionDetailResource
 *
 * Resource class untuk mentransformasi detail model AttendanceCorrection menjadi format JSON yang mendalam.
 */
class AttendanceCorrectionDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi detail koreksi absensi termasuk data karyawan, waktu yang diminta, dan status persetujuan.
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik koreksi absensi */
            'attendance' => [
                'actual_clock_in' => $this->attendance?->clock_in?->format('H:i:s'), /**< Waktu masuk aktual di sistem */
                'actual_clock_out' => $this->attendance?->clock_out?->format('H:i:s'), /**< Waktu keluar aktual di sistem */
            ],
            'requested_times' => [
                'clock_in' => $this->clock_in_requested?->format('H:i:s'), /**< Waktu masuk yang diajukan */
                'clock_out' => $this->clock_out_requested?->format('H:i:s'), /**< Waktu keluar yang diajukan */
            ],
            'reason' => $this->reason, /**< Alasan pengajuan koreksi */
            'attachment' => $this->attachment ? [
                'exists' => true, /**< Status keberadaan lampiran */
                'filename' => basename($this->attachment), /**< Nama file lampiran */
                'path' => $this->attachment, /**< Path file lampiran */
            ] : null,
            'status' => $this->status, /**< Status persetujuan (pending/approved/rejected) */
            'approval' => [
                'approved_by_name' => $this->approver?->user?->name ?? '-', /**< Nama pemberi persetujuan */
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'), /**< Waktu persetujuan diberikan */
                'note' => $this->note, /**< Catatan dari pemberi persetujuan */
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan pengajuan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
