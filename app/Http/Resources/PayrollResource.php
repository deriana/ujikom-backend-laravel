<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class PayrollResource
 *
 * Resource class untuk mentransformasi model Payroll menjadi format JSON yang ringkas untuk tampilan tabel/list.
 */
class PayrollResource extends JsonResource
{
    /**
     * Transform resource ke dalam array untuk tampilan tabel/list.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data ringkasan penggajian karyawan beserta status dan izin aksi.
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $statusDraft = 0;

        return [
            'uuid' => $this->uuid, /**< Identifier unik record payroll */
            'employee_name' => $this->employee?->user?->name, /**< Nama lengkap karyawan */
            'employee_nik' => $this->employee?->nik, /**< Nomor Induk Karyawan */
            'period_start' => $this->period_start->format('Y-m-d'), /**< Tanggal mulai periode penggajian */
            'period_end' => $this->period_end->format('Y-m-d'), /**< Tanggal berakhir periode penggajian */
            'net_salary' => $this->net_salary, /**< Gaji bersih yang diterima (Take Home Pay) */
            'gross_salary' => $this->gross_salary, /**< Total gaji kotor sebelum potongan */
            'manual_adjustment' => $this->manual_adjustment, /**< Nominal penyesuaian manual */
            'adjustment_note' => $this->adjustment_note, /**< Catatan alasan penyesuaian manual */
            'status' => $this->status, /**< Status pembayaran/proses payroll */
            'finalized_at' => $this->finalized_at, /**< Waktu ketika payroll diselesaikan/dikunci */
            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini */
                'update' => $this->status == $statusDraft && $user->can('update', $this->resource),
                'pay' => $this->status == $statusDraft && $user->can('pay', $this->resource),
            ],
        ];
    }
}
