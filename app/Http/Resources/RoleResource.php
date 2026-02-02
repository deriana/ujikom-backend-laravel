<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'system_reserve' => (bool) $this->system_reserve,
            'permissions' => $this->permissions->pluck('name'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
