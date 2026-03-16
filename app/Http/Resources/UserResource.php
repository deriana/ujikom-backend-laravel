<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'system_reserve' => $this->system_reserve,

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->pluck('name')
            ),

            'can' => [
                'update' => $this->is_active && $user?->can('update', $this->resource),
                'terminate' => $this->is_active && $user?->can('terminate', $this->resource),
                'change_password' => $user?->can('changePassword', $this->resource),
            ],

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'nik' => $this->employee->nik,
                    'status' => $this->employee->status_label,
                    'join_date' => $this->employee->join_date?->format('Y-m-d'), /**< Tanggal bergabung */
                    'resign_date' => $this->employee->resign_date?->format('Y-m-d'), /**< Tanggal pengunduran diri */
                    'contract_start' => $this->employee->contract_start?->format('Y-m-d'), /**< Tanggal mulai kontrak */
                    'contract_end' => $this->employee->contract_end?->format('Y-m-d'), /**< Tanggal berakhir kontrak */
                    'base_salary' => $this->employee->base_salary, /**< Gaji pokok karyawan */
                    'date_of_birth' => $this->employee->date_of_birth?->format('Y-m-d'), /**< Tanggal lahir */
                    'address' => $this->employee->address, /**< Alamat tempat tinggal */
                    'employment_state' => $this->employee->employment_state, /**< Status kondisi kerja */
                    'has_face_descriptor' => $this->employee->has_face_descriptor, /**< Status pendaftaran biometrik wajah */

                    'position' => [
                        'name' => $this->employee->position?->name, /**< Nama jabatan */
                        'base_salary' => $this->employee->position?->base_salary, /**< Gaji standar jabatan */
                        'allowances' => $this->employee->position?->allowances->map(function ($allowance) {
                            return [
                                'name' => $allowance->name, /**< Nama tunjangan */
                                'type' => $allowance->type, /**< Tipe tunjangan */
                                'amount' => $allowance->pivot->amount ?? $allowance->amount, /**< Nominal tunjangan */
                            ];
                        }),
                    ],
                    'team' => [
                        'name' => $this->employee->team?->name, /**< Nama tim */
                        'division' => $this->employee->team?->division?->name, /**< Nama divisi */
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name, /**< Nama atasan langsung */
                        'nik' => $this->employee->manager->nik, /**< NIK atasan langsung */
                    ] : null,

                    'phone' => $this->employee->phone, /**< Nomor telepon */
                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil */
                    'gender' => $this->employee->gender, /**< Jenis kelamin */
                    'leave_balances' => $this->when(isset($this->all_leave_types), fn() => $this->all_leave_types->map(function ($type) {
                        $balance = $this->employee->leaveBalances->where('leave_type_id', $type->id)->first();

                        return [
                            'leave_type' => $type->name, /**< Nama tipe cuti */
                            'year' => $balance->year ?? now()->year, /**< Tahun periode saldo */
                            'total_days' => $type->is_unlimited ? '∞' : ($balance->total_days ?? $type->default_days), /**< Total jatah hari */
                            'used_days' => $balance->used_days ?? 0, /**< Hari yang telah digunakan */
                            'remaining_days' => $type->is_unlimited ? '∞' : ($balance->remaining_days ?? $type->default_days), /**< Sisa hari cuti */
                            'is_unlimited' => (bool) $type->is_unlimited, /**< Status apakah cuti tidak terbatas */
                            'description' => $type->description, /**< Deskripsi tipe cuti */
                        ];
                    })),
                ];
            }),
        ];
    }
}
