<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reporter' => [
                'id' => $this->reporter->id ?? null,
                'name' => $this->reporter->user->name ?? 'Unknown',
            ],
            'operator' => [
                'id' => $this->operator->id ?? null,
                'name' => $this->operator->name ?? 'Unassigned',
            ],
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            
            'response_time' => $this->response_time ?? null, 

            'responses' => $this->whenLoaded('responses', function () {
                return $this->responses->map(function ($resp) {
                    return [
                        'id' => $resp->id,
                        'uuid' => $resp->uuid,
                        'responder_id' => $resp->responder_id,
                        'responder_name' => $resp->responder->name ?? 'System',
                        'response' => $resp->response,
                        'is_auto_reply' => $resp->is_auto_reply,
                        'created_at' => $resp->created_at->toISOString(),
                    ];
                });
            }),
            'rating' => $this->whenLoaded('rating', function () {
                if (!$this->rating) return null;
                return [
                    'rating' => $this->rating->rating,
                    'feedback' => $this->rating->feedback,
                    'created_at' => $this->rating->created_at->toISOString(),
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}