<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'teams' => $this->whenLoaded('teams', function () {
                return $this->teams->pluck('name');
            }),
        ];
    }
}
