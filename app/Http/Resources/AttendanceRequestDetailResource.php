<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRequestDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'request_type' => $this->request_type,
            'reason' => $this->reason,
            'status' => $this->status,
            'note' => $this->note,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,

            // Info Karyawan Lengkap
            'employee' => [
                'name' => $this->employee->user->name ?? null,
                'nik' => $this->employee->nik ?? null,
                'position' => $this->employee->position->name ?? null, // Tambahan jika ada
            ],

            // Detail Perubahan (Kondisional)
            'change_details' => $this->getChangeDetails(),

            // Info Approver & Audit
            'approval_info' => [
                'is_processed' => $this->approved_by_id !== null,
                'approver_name' => $this->approver->name ?? null,
                'processed_at' => $this->updated_at->format('Y-m-d H:i:s'),
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Helper untuk merapikan detail perubahan berdasarkan type
     */
    private function getChangeDetails(): ?array
    {
        // 1. Jika Request Ganti Shift (Harian)
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

        // 2. Jika Request Ganti Jadwal (Rentang Waktu)
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
