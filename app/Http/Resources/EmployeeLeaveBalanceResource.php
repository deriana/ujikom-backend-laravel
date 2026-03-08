<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveBalanceResource extends JsonResource
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
            'nik' => $this->nik,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'profile_photo' => $this->getFirstMediaUrl('profile_photo') ?: null,
            'position' => $this->whenLoaded('position', fn () => $this->position?->name),
            'leave_balances' => $this->whenLoaded('leaveBalances', function () {
                return $this->leaveBalances->map(function ($balance) {
                    return [
                        'leave_type' => $balance->leaveType?->name,
                        'year' => $balance->year,
                        'total_days' => $balance->total_days,
                        'used_days' => $balance->used_days,
                        'remaining_days' => $balance->remaining_days,
                        'is_unlimited' => $balance->leaveType?->is_unlimited,
                    ];
                });
            }),
        ];
    }
}
