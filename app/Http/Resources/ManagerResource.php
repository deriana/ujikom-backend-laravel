<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // $this di sini merujuk pada model User karena getManagers() mengembalikan User
        return [
            'name' => $this->name,
            'nik' => $this->employee?->nik,
            'role' => $this->roles->first()?->name, // Menampilkan 'manager' atau 'director'
            'position' => $this->employee?->position?->name, // Menampilkan nama jabatan (ex: 'Manager Backend')
        ];
    }
}
