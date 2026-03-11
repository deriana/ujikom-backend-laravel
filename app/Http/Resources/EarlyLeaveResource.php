<?php

namespace App\Http\Resources;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class EarlyLeaveResource
 *
 * Resource class untuk mentransformasi model EarlyLeave menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class EarlyLeaveResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data pengajuan pulang awal beserta status dan izin aksi.
     */
    public function toArray($request)
    {
        $user = Auth::user();

        $myEmployeeId = $user->employee?->id;

        $requesterManagerId = $this->employee?->manager_id;

        $isApprover = ($requesterManagerId == $myEmployeeId) && ($this->status === ApprovalStatus::PENDING->value);
        $isManager = ($this->employee?->manager_id == $myEmployeeId);
        $isDirector = $user->hasRole(UserRole::DIRECTOR->value);
        $isHr = $user->hasRole(UserRole::HR->value);

        $canApprove = ($this->status === ApprovalStatus::PENDING->value) && ($isManager || $isDirector || $isHr);

        return [
            'uuid' => $this->uuid, /**< Identifier unik pengajuan pulang awal */
            'employee_name' => $this->employee?->user?->name, /**< Nama karyawan yang mengajukan */
            'employee_nik' => $this->employee?->nik, /**< NIK karyawan yang mengajukan */
            'reason' => $this->reason, /**< Alasan pengajuan pulang awal */
            'date' => $this->attendance?->date->format('Y-m-d'), /**< Tanggal absensi terkait */
            'minutes_early' => $this->minutes_early, /**< Durasi pulang awal dalam menit */
            'status' => $this->status, /**< Status persetujuan saat ini */
            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                // 'approve' => $user->can('approve', $this->resource) && $isApprover,
                'approve' => $user->can('approve', $this->resource)
            ],
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'), /**< Waktu persetujuan diberikan */
            'created_at' => $this->created_at?->format('Y-m-d'), /**< Waktu pembuatan pengajuan */
        ];
    }
}
