<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOpen = $this->status === 'open';

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reporter' => [
                'id' => $this->reporter->id ?? null,
                // Mengasumsikan employee punya relasi ke user untuk ambil nama
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
            
            // Logika Permission
            'can' => [
                /** * User bisa update/delete jika:
                 * 1. Punya izin di Policy
                 * 2. DAN Status tiket masih 'open'
                 */
                'update' => $user->can('update', $this->resource) && $isOpen,
                'delete' => $user->can('delete', $this->resource) && $isOpen,
                
                // Tambahan: Permission untuk membalas atau memberi rating biasanya tidak terbatas status 'open'
                'reply' => $user->can('reply', $this->resource) && $this->status !== 'closed',
                'rate'  => $user->can('rate', $this->resource) && $this->status === 'closed',
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}