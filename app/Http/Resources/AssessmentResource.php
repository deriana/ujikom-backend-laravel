<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'evaluatee_name' => $this->evaluatee?->user?->name ?? '-',
            'evaluatee_nik' => $this->evaluatee?->nik ?? '-',
            'evaluator_name' => $this->evaluator?->user?->name ?? '-',
            'evaluator_nik' => $this->evaluator?->nik ?? '-',

            // Menampilkan list lengkap kategori dan skornya masing-masing
            'assessment_details' => $this->assessments_details->map(function ($detail) {
                return [
                    'category_uuid' => $detail->category?->uuid, // WAJIB ADA untuk identifier di Frontend
                    'category_name' => $detail->category?->name ?? $detail->old_category_name,
                    'score' => $detail->score,
                    'bonus_salary' => $detail->bonus_salary,
                ];
            }),

            'period' => Carbon::parse($this->period)->format('Y-m'),
            'note' => $this->note, // Hilangkan limit kalau mau full data
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }
}
