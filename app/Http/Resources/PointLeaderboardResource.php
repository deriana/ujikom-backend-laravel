<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointLeaderboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'meta' => [
                'period' => $this['period'],
                'my_rank' => $this['my_rank'],
                'my_points' => (int) $this['my_points'],
            ],
            'list' => $this['leaderboard']->map(function ($wallet) {
                return [
                    'nik' => $wallet->employee->nik,
                    'name' => $wallet->employee->user->name,
                    'position' => $wallet->employee->position?->name ?? '-',
                    'total_points' => (int) $wallet->current_balance,
                    'photo_url' => $wallet->employee->getFirstMediaUrl('profile_photo') ?: null,
                    'rank' => $wallet->rank,
                ];
            }),
        ];
    }
}
