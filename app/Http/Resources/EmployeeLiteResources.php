<?php

namespace  App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLiteResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'nik' => $this->nik,
            'name' => $this->user?->name,
        ];
    }
}
