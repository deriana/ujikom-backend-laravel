<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * Class AssessmentDetailResource
 *
 * Resource class untuk mentransformasi detail model Assessment menjadi format JSON yang mendalam.
 */
class AssessmentDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi detail penilaian termasuk data evaluatee, evaluator, dan rincian skor per kategori.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid, /**< Identifier unik penilaian */
            'period'       => Carbon::parse($this->period)->format('Y-m'), /**< Periode penilaian (Format: YYYY-MM) */
            'note'         => $this->note, /**< Catatan atau feedback tambahan */

          'evaluatee' => [
                'name'  => $this->evaluatee?->user?->name, /**< Nama karyawan yang dinilai */
                'nik'   => $this->evaluatee?->nik,         /**< NIK karyawan yang dinilai */
                'photo' => $this->evaluatee?->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil evaluatee */
            ],

            'evaluator' => [
                'name'  => $this->evaluator?->user?->name, /**< Nama penilai */
                'nik'   => $this->evaluator?->nik,         /**< NIK penilai */
                'photo' => $this->evaluator?->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil evaluator */
            ],

            'scores' => $this->assessments_details->map(function ($detail) {
                return [
                    'category_name' => $detail->category?->name, /**< Nama kategori penilaian */
                    'score'         => $detail->score,         /**< Nilai yang diberikan */
                    'bonus_salary'  => $detail->bonus_salary,  /**< Nominal bonus berdasarkan skor */
                ];
            }),

            'created_at' => $this->created_at->toDateTimeString(), /**< Waktu pembuatan record */
        ];
    }
}
