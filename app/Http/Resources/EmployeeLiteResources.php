<?php

namespace  App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EmployeeLiteResources
 *
 * Resource class untuk mentransformasi model Employee menjadi format JSON yang sangat ringkas,
 * biasanya digunakan untuk dropdown atau list pencarian sederhana.
 */
class EmployeeLiteResources extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data minimal karyawan (NIK dan Nama).
     */
    public function toArray($request)
    {
        return [
            'nik' => $this->nik, /**< Nomor Induk Karyawan */
            'name' => $this->user?->name, /**< Nama lengkap karyawan dari relasi user */
        ];
    }
}
