<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class LeaveResource
 *
 * Resource class untuk mentransformasi model Leave menjadi format JSON yang ringkas untuk tampilan tabel/list,
 * termasuk informasi alur persetujuan berjenjang.
 */
class LeaveResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data pengajuan cuti beserta status persetujuan dan izin aksi.
     */
    public function toArray($request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $currentApprovalUuid = null;
        $canApprove = false;

        $pendingApproval = $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level', 'asc')
            ->first();

        if ($pendingApproval) {
            $isTargetApprover = $employee && $pendingApproval->approver_id === $employee->id;
            $isAdmin = $user->hasRole(UserRole::ADMIN);

            if ($isTargetApprover || $isAdmin) {
                $currentApprovalUuid = $pendingApproval->uuid;
                $canApprove = true;
            }
        }

        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan cuti */
            'current_approval_uuid' => $currentApprovalUuid, /**< UUID record persetujuan yang sedang aktif/pending */
            'employee_name' => $this->employee->user->name ?? '-', /**< Nama karyawan yang mengajukan */
            'employee_nik' => $this->employee->nik ?? '-', /**< NIK karyawan yang mengajukan */
            'leave_type_uuid' => $this->leaveType->uuid, /**< Identifier unik tipe cuti */
            'leave_type' => $this->leaveType->name, /**< Nama tipe cuti (misal: Cuti Tahunan) */
            'date_start' => $this->date_start->format('Y-m-d'), /**< Tanggal mulai cuti */
            'date_end' => $this->date_end->format('Y-m-d'), /**< Tanggal berakhir cuti */
            'reason' => $this->reason, /**< Alasan pengajuan cuti */
            'attachment' => $this->attachment ? [
                'exists' => true, /**< Status keberadaan lampiran */
                'filename' => basename($this->attachment), /**< Nama file lampiran */
                'path' => $this->attachment, /**< Path file lampiran */
            ] : null,
            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'approve' => $canApprove,
            ],
            'approval_levels' => $this->approvals->map(function ($approval) {
                return [ /**< Detail status persetujuan di setiap level */
                    'level' => $approval->level, /**< Tingkat level persetujuan */
                    'status' => $approval->status, /**< Status pada level ini */
                    'nama_approver' => $approval->approver->user->name, /**< Nama pejabat pemberi persetujuan */
                ];
            }),
            'current_level' => $this->approvals->max('level'), /**< Level tertinggi dalam alur persetujuan ini */
            'approval_status' => $this->approval_status, /**< Status persetujuan akhir (pending/approved/rejected) */
            'is_half_day' => $this->is_half_day, /**< Status apakah cuti setengah hari */
            'duration' => $this->duration, /**< Durasi cuti dalam angka */
            'duration_label' => $this->duration_text, /**< Label durasi (misal: 2 Hari) */
            'next_approver' => optional($this->nextApprover())->name, /**< Nama approver selanjutnya yang ditunggu */
        ];
    }
}
