<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AttendanceRequestDetailResource
 *
 * Resource class untuk mentransformasi detail model AttendanceRequest menjadi format JSON yang mendalam.
 */
class AttendanceRequestDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi detail pengajuan kehadiran termasuk data karyawan, detail perubahan, dan info persetujuan.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan */
            'request_type' => $this->request_type, /**< Tipe pengajuan (misal: shift_change, schedule_change) */
            'reason' => $this->reason, /**< Alasan pengajuan */
            'status' => $this->status, /**< Status persetujuan saat ini */
            'note' => $this->note, /**< Catatan dari pemberi persetujuan */
            'start_date' => $this->start_date, /**< Tanggal mulai berlakunya perubahan */
            'end_date' => $this->end_date, /**< Tanggal berakhirnya perubahan */

            'employee' => [
                'name' => $this->employee->user->name ?? null, /**< Nama karyawan yang mengajukan */
                'nik' => $this->employee->nik ?? null, /**< NIK karyawan */
                'position' => $this->employee->position->name ?? null, /**< Nama jabatan karyawan */
            ],

            'change_details' => $this->getChangeDetails(),

            'approval_info' => [
                'is_processed' => $this->approved_by_id !== null, /**< Status apakah pengajuan sudah diproses */
                'approver_name' => $this->approver->name ?? null, /**< Nama pemberi persetujuan */
                'processed_at' => $this->updated_at->format('Y-m-d H:i:s'), /**< Waktu pengajuan diproses */
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'), /**< Waktu pembuatan pengajuan */
        ];
    }

    /**
     * Mendapatkan detail teknis mengenai perubahan yang diajukan (Shift atau Jadwal Kerja).
     *
     * @return array|null Detail perubahan berdasarkan template shift atau jadwal kerja
     */
    private function getChangeDetails(): ?array
    {
        if ($this->shift_template_id) {
            return [
                'type' => 'shift',
                'template_name' => $this->shiftTemplate->name,
                'start_time' => $this->shiftTemplate->start_time?->format('H:i'),
                'end_time' => $this->shiftTemplate->end_time?->format('H:i'),
                'late_tolerance' => $this->shiftTemplate->late_tolerance_minutes . ' menit',
                'is_cross_day' => $this->shiftTemplate->cross_day,
            ];
        }

        if ($this->work_schedules_id) {
            return [
                'type' => 'work_schedule',
                'schedule_name' => $this->workSchedule->name,
                'work_mode' => $this->workSchedule->workMode->name ?? 'N/A',
                'times' => [
                    'work' => $this->workSchedule->work_start_time . ' - ' . $this->workSchedule->work_end_time,
                    'break' => $this->workSchedule->break_start_time . ' - ' . $this->workSchedule->break_end_time,
                ],
                'requires_location' => (bool) $this->workSchedule->requires_office_location,
            ];
        }

        return null;
    }
}
