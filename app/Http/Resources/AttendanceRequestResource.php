<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class AttendanceRequestResource
 *
 * Resource class untuk mentransformasi model AttendanceRequest menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class AttendanceRequestResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data pengajuan kehadiran (perubahan shift/jadwal) beserta status dan izin aksi.
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($myEmployeeId && $requesterManagerId == $myEmployeeId)
                      && ($this->status === ApprovalStatus::PENDING->value);

        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan */
            'request_type' => $this->request_type, /**< Tipe pengajuan (shift_change atau schedule_change) */
            'reason' => $this->reason, /**< Alasan pengajuan */
            'status' => $this->status, /**< Status persetujuan saat ini */
            'start_date' => $this->start_date, /**< Tanggal mulai berlakunya perubahan */
            'end_date' => $this->end_date, /**< Tanggal berakhirnya perubahan */
            'employee' => [
                'name' => $this->employee?->user?->name, /**< Nama karyawan yang mengajukan */
                'nik' => $this->employee?->nik, /**< NIK karyawan */
            ],
            'shift_details' => $this->when($this->shift_template_id, function () {
                return [
                    'uuid' => $this->shiftTemplate?->uuid, /**< Identifier unik template shift */
                    'name' => $this->shiftTemplate?->name, /**< Nama template shift baru */
                ];
            }),
            'work_schedule_details' => $this->when($this->work_schedules_id, function () {
                return [
                    'uuid' => $this->workSchedule?->uuid, /**< Identifier unik jadwal kerja */
                    'name' => $this->workSchedule?->name, /**< Nama jadwal kerja baru */
                ];
            }),
            'approved_by' => $this->when($this->approved_by_id, function () {
                return [
                    'name' => $this->approver?->user?->name ?? 'System', /**< Nama pemberi persetujuan */
                ];
            }),
            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $user->can('approve', $this->resource),
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan pengajuan */
        ];
    }
}
