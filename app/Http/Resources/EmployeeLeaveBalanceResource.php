<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Class EmployeeLeaveBalanceResource
 *
 * Resource class untuk mentransformasi data saldo cuti karyawan menjadi format JSON,
 * menggabungkan saldo yang tersimpan dengan tipe cuti yang bersifat tidak terbatas (unlimited).
 */
class EmployeeLeaveBalanceResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data profil karyawan beserta rincian saldo cutinya.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid ?? null,
            'nik'           => $this->nik ?? null,
            'name'          => $this->user?->name ?? null,
            'email'         => $this->user?->email ?? null,
            'profile_photo' => $this->relationLoaded('media') ? ($this->getFirstMediaUrl('profile_photo') ?: null) : null,
            'position'      => $this->relationLoaded('position') ? ($this->position?->name ?? null) : null,
            'leave_balances' => $this->prepareBalances(),
        ];
    }

    /**
     * Menyiapkan dan menggabungkan data saldo cuti.
     *
     * @return array Daftar saldo cuti termasuk penanganan khusus untuk tipe cuti unlimited.
     */
    protected function prepareBalances(): array
    {
        $year = Carbon::now()->year;

        // 1. Ambil saldo yang sudah ada di database (Eager Loaded)
        $balances = collect($this->leaveBalances)->map(function ($balance) {
            return [
                'leave_type'     => $balance->leaveType?->name,
                'year'           => (int) $balance->year,
                'total_days'     => (int) $balance->total_days,
                'used_days'      => (int) $balance->used_days,
                // 'remaining_days' => (int) $balance->remaining_days,
                'is_unlimited'   => (bool) ($balance->leaveType?->is_unlimited ?? false),
            ];
        });

        // 2. Cari tipe cuti yang bersifat UNLIMITED tapi belum ada di record saldo user
        $unlimitedTypes = LeaveType::where('is_unlimited', true)
            ->where('is_active', true)
            ->get();

        foreach ($unlimitedTypes as $type) {
            $exists = $balances->contains('leave_type', $type->name);

            if (!$exists) {
                $balances->push([
                    'leave_type'     => $type->name,
                    'year'           => $year,
                    'total_days'     => 0,
                    'used_days'      => 0,
                    // 'remaining_days' => 0,
                    'is_unlimited'   => true,
                ]);
            }
        }

        return $balances->toArray();
    }
}
