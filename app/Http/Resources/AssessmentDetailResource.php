<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AssessmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'period'       => Carbon::parse($this->period)->format('Y-m'),
            'note'         => $this->note,

          'evaluatee' => [
                'name'  => $this->evaluatee?->user?->name, // Ambil dari User
                'nik'   => $this->evaluatee?->nik,         // Ambil dari Employee
                'photo' => $this->evaluatee?->getFirstMediaUrl('profile_photo') ?: null,
            ],

            'evaluator' => [
                'name'  => $this->evaluator?->user?->name, // Ambil dari User
                'nik'   => $this->evaluator?->nik,
                'photo' => $this->evaluator?->getFirstMediaUrl('profile_photo') ?: null,
            ],

            'scores' => $this->assessments_details->map(function ($detail) {
                return [
                    'category_name' => $detail->category?->name,
                    'score'         => $detail->score,
                    'bonus_salary'  => $detail->bonus_salary,
                ];
            }),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
