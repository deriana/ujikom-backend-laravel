<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class DivisionResource
 *
 * Resource class untuk mentransformasi model Division menjadi format JSON.
 */
class DivisionResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data divisi termasuk kode, status sistem, pembuat, dan daftar tim terkait.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik divisi */
            'name' => $this->name, /**< Nama divisi */
            'code' => $this->code, /**< Kode unik divisi */
            'system_reserve' => $this->system_reserve, /**< Status apakah divisi ini merupakan cadangan sistem (tidak boleh dihapus) */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'), /**< Waktu pembuatan */
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'), /**< Waktu pembaruan terakhir */
            'creator' => $this->whenLoaded('creator', function () { /**< Data pengguna yang membuat record ini */
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'teams' => $this->whenLoaded('teams', function () { /**< Daftar tim yang berada di bawah divisi ini */
                return $this->teams->map(function ($team) {
                    return [
                        'uuid' => $team->uuid,
                        'name' => $team->name,
                    ];
                });
            }),
        ];
    }
}
