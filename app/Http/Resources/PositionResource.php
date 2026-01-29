<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'base_salary' => (float) $this->base_salary,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'uuid' => $this->creator->uuid,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'allowances' => $this->whenLoaded('allowances', function () {
                return $this->allowances->map(function ($position) {
                    return [
                        'uuid' => $position->uuid,
                        'name' => $position->name,
                        'amount' => isset($position->pivot->amount)
                            ? (float) $position->pivot->amount
                            : (float) $this->amount,
                    ];
                });
            }),
        ];
    }
}
