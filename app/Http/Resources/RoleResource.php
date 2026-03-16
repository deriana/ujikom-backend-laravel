<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RoleResource
 *
 * Resource class untuk mentransformasi model Role menjadi format JSON,
 * mencakup informasi nama role, status sistem, dan daftar hak akses (permissions) terkait.
 */
class RoleResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed> Representasi data role beserta daftar izinnya.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id, /**< Identifier unik role */
            'name' => $this->name, /**< Nama role (misal: admin, employee) */
            'system_reserve' => (bool) $this->system_reserve, /**< Status apakah role ini merupakan cadangan sistem */
            'permissions' => $this->permissions->map(function ($permission) { /**< Daftar izin yang dimiliki oleh role ini */
                return [
                    'id' => $permission->id, /**< Identifier unik izin */
                    'name' => $permission->name, /**< Nama teknis izin */
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'), /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
        ];
    }
}
