<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AssessmentResource
 *
 * Resource class untuk mentransformasi model Assessment menjadi format JSON yang ringkas.
 */
class AssessmentResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data penilaian termasuk ringkasan evaluatee, evaluator, dan rincian skor.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik penilaian */
            'evaluatee_name' => $this->evaluatee?->user?->name ?? '-', /**< Nama karyawan yang dinilai */
            'evaluatee_nik' => $this->evaluatee?->nik ?? '-', /**< NIK karyawan yang dinilai */
            'evaluator_name' => $this->evaluator?->user?->name ?? '-', /**< Nama penilai */
            'evaluator_nik' => $this->evaluator?->nik ?? '-', /**< NIK penilai */

            // Menampilkan list lengkap kategori dan skornya masing-masing
            'assessment_details' => $this->assessments_details->map(function ($detail) {
                return [
                    'category_uuid' => $detail->category?->uuid, /**< Identifier unik kategori penilaian */
                    'category_name' => $detail->category?->name ?? $detail->old_category_name, /**< Nama kategori penilaian */
                    'score' => $detail->score, /**< Nilai yang diberikan */
                    'bonus_salary' => $detail->bonus_salary, /**< Nominal bonus berdasarkan skor */
                ];
            }),

            'period' => Carbon::parse($this->period)->format('Y-m'), /**< Periode penilaian (Format: YYYY-MM) */
            'note' => $this->note, /**< Catatan atau feedback tambahan */
            'created_at' => $this->created_at->format('d/m/Y'), /**< Tanggal pembuatan record */
        ];
    }
}
