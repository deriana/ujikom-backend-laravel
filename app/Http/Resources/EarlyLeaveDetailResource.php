<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EarlyLeaveDetailResource
 *
 * Resource class untuk mentransformasi detail model EarlyLeave menjadi format JSON yang mendalam.
 */
class EarlyLeaveDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi detail pengajuan pulang awal termasuk data karyawan, alasan, lampiran, dan status persetujuan.
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan pulang awal */
            'date' => $this->attendance?->date?->format('Y-m-d'), /**< Tanggal absensi terkait */
            'employee' => [
                'name' => $this->employee?->user?->name, /**< Nama karyawan yang mengajukan */
                'nik' => $this->employee?->nik, /**< NIK karyawan */
            ],
            'minutes_early' => $this->minutes_early, /**< Durasi pulang awal dalam menit */
            'reason' => $this->reason, /**< Alasan pengajuan pulang awal */
            'attachment' => $this->attachment ? [
                'exists' => true, /**< Status keberadaan lampiran */
                'filename' => basename($this->attachment), /**< Nama file lampiran */
                'path' => $this->attachment, /**< Path file lampiran */
                'download_url' => url('/api/early_leaves/download-attachment/'.basename($this->attachment)), /**< URL untuk mengunduh lampiran */
            ] : null,
            'status' => $this->status, /**< Status persetujuan (pending/approved/rejected) */
            'approval' => [
                'approved_by_name' => $this->manager?->user?->name, /**< Nama manajer yang memberikan persetujuan */
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'), /**< Waktu persetujuan diberikan */
                'note' => $this->note, /**< Catatan dari pemberi persetujuan */
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan pengajuan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
