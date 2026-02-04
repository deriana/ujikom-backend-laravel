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
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'teams' => $this->whenLoaded('teams', function () {
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
