<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class LeaveDetailResource
 *
 * Resource class untuk mentransformasi detail model Leave menjadi format JSON yang mendalam,
 * mencakup informasi karyawan, tipe cuti, riwayat persetujuan, dan saldo cuti terkait.
 */
class LeaveDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi detail pengajuan cuti yang komprehensif.
     */
    public function toArray($request)
    {
        $balance = $this->employee->leaveBalances()
            ->where('leave_type_id', $this->leave_type_id)
            ->first();

        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan cuti */
            'employee' => [
                'name' => $this->employee->user->name ?? 'N/A', /**< Nama karyawan yang mengajukan */
                'nik' => $this->employee->nik ?? '-', /**< NIK karyawan */
                'job_position' => $this->employee->position?->name ?? '-', /**< Nama jabatan karyawan */
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?? null, /**< URL foto profil */
            ],
            'leave_type' => [
                'name' => $this->leaveType?->name ?? 'Unknown', /**< Nama tipe cuti */
                'default_days' => $this->leaveType->default_days, /**< Jatah hari default untuk tipe cuti ini */
                'is_active' => $this->leaveType->is_active, /**< Status keaktifan tipe cuti */
                'gender' => $this->leaveType->gender, /**< Batasan gender untuk tipe cuti ini */
                'requires_family_status' => $this->leaveType->requires_family_status, /**< Status apakah memerlukan data status keluarga */
            ],
            'date_start' => $this->date_start->format('Y-m-d'), /**< Tanggal mulai cuti */
            'date_end' => $this->date_end->format('Y-m-d'), /**< Tanggal berakhir cuti */
            'is_half_day' => $this->is_half_day, /**< Status apakah cuti setengah hari */
            'reason' => $this->reason, /**< Alasan pengajuan cuti */
            'attachment' => $this->attachment ? [
                'exists' => true, /**< Status keberadaan lampiran */
                'filename' => basename($this->attachment), /**< Nama file lampiran */
                'path' => $this->attachment, /**< Path file lampiran */
                'download_url' => url('/api/leaves/download-attachment/'.basename($this->attachment)), /**< URL untuk mengunduh lampiran */
            ] : null,
            'approval_status' => $this->approval_status, /**< Status persetujuan akhir (pending/approved/rejected) */
            'next_approver' => optional($this->nextApprover())->name, /**< Nama approver selanjutnya dalam alur hierarki */
            'approvals' => $this->approvals->map(function ($approval) {
                return [ /**< Daftar riwayat persetujuan berjenjang */
                    'uuid' => $approval->uuid, /**< Identifier unik record persetujuan */
                    'approver' => [
                        'name' => $approval->approver?->name ?? 'System', /**< Nama pemberi persetujuan */
                        // 'role' => $approval->approver->role,
                    ],
                    'level' => $approval->level, /**< Tingkat/level persetujuan */
                    'status' => $approval->status, /**< Status persetujuan pada level ini */
                    'approved_at' => optional($approval->approved_at)->format('Y-m-d H:i:s'), /**< Waktu persetujuan diberikan */
                    'note' => $approval->note, /**< Catatan dari approver */
                ];
            }),
            'employee_leave_detail' => optional($this->employeeLeave)->only([
                'start_date', 'end_date', 'days_taken', 'status', /**< Detail teknis implementasi cuti di sistem */
            ]),
            'leave_balance' => $balance ? [
                'year' => $balance->year, /**< Tahun periode saldo cuti */
                'total_days' => $balance->total_days, /**< Total jatah cuti tahunan */
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->getRemainingDaysAttribute(),
            ] : null,
        ];
    }
}
