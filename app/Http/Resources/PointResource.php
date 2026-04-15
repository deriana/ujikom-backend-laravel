<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'points' => $this->current_points,
            // Menentukan tipe warna untuk frontend (Reward/Penalty)
            'type' => $this->current_points >= 0 ? 'reward' : 'penalty',

            // Relasi Employee
            'employee' => [
                'nik' => $this->employee->nik,
                'name' => $this->employee->user->name,
                'photo' => $this->employee->getFirstMediaUrl('profile_photo'),
            ],

            // Relasi Rule (Alasan Poin)
            'rule' => [
                'event_name' => $this->rule->event_name,
                'description' => $this->rule->description,
            ],

            // Relasi Periode
            'period' => [
                'name' => $this->period->name,
                'is_active' => (bool)$this->period->is_active,
            ],

            // // Status Void (Keamanan)
            // 'is_void' => (bool)$this->is_void,
            // 'void_reason' => $this->void_reason,

            'created_at' => $this->created_at->format('d M Y H:i'),
        ];
    }
}
