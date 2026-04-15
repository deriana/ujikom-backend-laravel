<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointMutationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @interface PointMutation
     * @property {string} [uuid]
     * @property {"incoming" | "outgoing"} type
     * @property {number} amount
     * @property {string} description
     * @property {string} [item_uuid]
     * @property {string} [item_name]
     * @property {string} date
     * @property {string} [date_human]
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource['uuid'] ?? null,
            'type' => $this->resource['type'],
            'amount' => (int) $this->resource['amount'],
            'description' => $this->resource['description'],
            'item_uuid' => $this->resource['item_uuid'] ?? null,
            'item_name' => $this->resource['item_name'] ?? null,
            'date' => $this->resource['date']->format('Y-m-d H:i:s'),
            'date_human' => $this->resource['date']->diffForHumans(),
        ];
    }
}
