<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Class UserDetailResource
 *
 * Resource class untuk mentransformasi detail model User menjadi format JSON yang mendalam,
 * mencakup informasi akun, role, izin aksi, dan detail data kepegawaian yang komprehensif.
 */
class UserDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array untuk tampilan detail.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi detail user dan data employee terkait.
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        return [
            'uuid' => $this->uuid, /**< Identifier unik user */
            'name' => $this->name, /**< Nama lengkap user */
            'email' => $this->email, /**< Alamat email user */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pendaftaran akun */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
            'is_active' => $this->is_active, /**< Status keaktifan akun user */
            'system_reserve' => $this->system_reserve, /**< Status apakah user merupakan cadangan sistem */

            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name') /**< Daftar nama role yang dimiliki */
            ),

            'can' => [ /**< Izin aksi yang dapat dilakukan oleh pengguna saat ini terhadap resource ini */
                'update' => $user ? $user->can('update', $this->resource) : false,
            ],

            'employee' => $this->whenLoaded('employee', function () { /**< Detail data kepegawaian jika user adalah karyawan */
                return [
                    'nik' => $this->employee->nik, /**< Nomor Induk Karyawan */
                    'status' => $this->employee->status_label, /**< Label status keaktifan karyawan */
                    'employee_status' => $this->employee->employee_status, /**< Status kepegawaian (Tetap/Kontrak) */
                    'base_salary' => $this->employee->base_salary, /**< Gaji pokok karyawan */

                    'position' => [
                        'uuid' => $this->employee->position?->uuid, /**< Identifier unik jabatan */
                        'name' => $this->employee->position?->name, /**< Nama jabatan */
                        'base_salary' => $this->employee->position?->base_salary, /**< Gaji pokok standar jabatan */
                        'allowances' => $this->employee->position?->allowances->map(function ($allowance) {
                            return [ /**< Daftar tunjangan yang melekat pada jabatan */
                                'name' => $allowance->name,
                                'amount' => $allowance->amount,
                            ];
                        }),
                    ],

                    'team' => [
                        'uuid' => $this->employee->team?->uuid, /**< Identifier unik tim */
                        'name' => $this->employee->team?->name, /**< Nama tim */
                        'division' => $this->employee->team?->division?->name, /**< Nama divisi terkait */
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name, /**< Nama atasan langsung */
                        'nik' => $this->employee->manager->nik, /**< NIK atasan langsung */
                    ] : null,

                    // tambahan fields baru
                    'has_face_descriptor' => $this->employee->biometrics ? true : false, /**< Status apakah data wajah sudah terdaftar */
                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil */
                    'phone' => $this->employee->phone, /**< Nomor telepon */
                    'gender' => $this->employee->gender, /**< Jenis kelamin */
                    'date_of_birth' => $this->employee->date_of_birth?->format('Y-m-d'), /**< Tanggal lahir */
                    'address' => $this->employee->address, /**< Alamat tempat tinggal */
                    'join_date' => $this->employee->join_date?->format('Y-m-d'), /**< Tanggal bergabung */
                    'resign_date' => $this->employee->resign_date?->format('Y-m-d'), /**< Tanggal pengunduran diri */
                    'contract_start' => $this->employee->contract_start?->format('Y-m-d'), /**< Tanggal mulai kontrak */
                    'contract_end' => $this->employee->contract_end?->format('Y-m-d'), /**< Tanggal berakhir kontrak */
                    'employment_state' => $this->employee->employment_state, /**< Status kondisi kerja (misal: active, terminated) */
                    'termination_date' => $this->employee->termination_date?->format('Y-m-d'), /**< Tanggal pemutusan hubungan kerja */
                    'termination_reason' => $this->employee->termination_reason, /**< Alasan pemutusan hubungan kerja */
                ];
            }),
        ];
    }
}
