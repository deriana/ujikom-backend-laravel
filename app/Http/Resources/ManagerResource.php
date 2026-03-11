<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ManagerResource
 *
 * Resource class untuk mentransformasi model User yang bertindak sebagai Manager menjadi format JSON yang ringkas,
 * biasanya digunakan untuk daftar pilihan (dropdown) atasan atau informasi manajer terkait.
 */
class ManagerResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data manajer mencakup nama, NIK, role, dan jabatan.
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name, /**< Nama lengkap manajer */
            'nik' => $this->employee?->nik, /**< Nomor Induk Karyawan (NIK) manajer */
            'role' => $this->roles->first()?->name, /**< Nama role utama yang dimiliki */
            'position' => $this->employee?->position?->name, /**< Nama jabatan manajer */
        ];
    }
}
