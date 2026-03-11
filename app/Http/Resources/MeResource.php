<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class MeResource
 *
 * Resource class untuk mentransformasi data profil pengguna yang sedang login (current user)
 * menjadi format JSON, mencakup informasi dasar user, role, dan detail data karyawan.
 */
class MeResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data profil mandiri pengguna.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik user */
            'name' => $this->name, /**< Nama lengkap user */
            'email' => $this->email, /**< Alamat email user */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pendaftaran akun */

            'roles' => RoleResource::collection($this->whenLoaded('roles')), /**< Daftar role yang dimiliki user */

            'employee' => $this->whenLoaded('employee', function () {
                return [ /**< Detail data kepegawaian jika user adalah karyawan */
                    'nik' => $this->employee->nik, /**< Nomor Induk Karyawan */
                    'status' => $this->employee->status_label, /**< Label status keaktifan */
                    'base_salary' => $this->employee->base_salary, /**< Gaji pokok karyawan */
                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil */

                    'position' => [
                        'name' => $this->employee->position?->name, /**< Nama jabatan saat ini */
                    ],

                    'team' => [
                        'name' => $this->employee->team?->name, /**< Nama tim tempat bekerja */
                        'division' => $this->employee->team?->division?->name, /**< Nama divisi terkait */
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name, /**< Nama atasan langsung */
                        'nik' => $this->employee->manager->nik, /**< NIK atasan langsung */
                    ] : null,
                ];
            }),
        ];
    }
}
