<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $statusDraft = 0;

        return [
            'uuid' => $this->uuid,
            'employee_name' => $this->employee?->user?->name,
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'net_salary' => $this->net_salary,
            'gross_salary' => $this->gross_salary,
            'manual_adjustment' => $this->manual_adjustment,
            'adjustment_note' => $this->adjustment_note,
            'status' => $this->status,
            'finalized_at' => $this->finalized_at,
            'can' => [
                'update' => $this->status == $statusDraft && $user->can('update', $this->resource),
                'pay' => $this->status == $statusDraft && $user->can('pay', $this->resource),
            ],
        ];
    }
}
