<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluatee_nik' => 'required|exists:employees,nik',
            'period'        => 'required|string', // Karena dari frontend biasanya format YYYY-MM
            'note'          => 'nullable|string',

            // Validasi untuk array assessment_details
            'assessment_details'                 => 'required|array|min:1',
            'assessment_details.*.category_uuid' => 'required|exists:assessment_categories,uuid',
            'assessment_details.*.score'         => 'required|integer|min:1|max:5',
            'assessment_details.*.bonus_salary'  => 'nullable|numeric',
        ];
    }

    /**
     * Custom attributes agar pesan error lebih enak dibaca
     */
    public function attributes(): array
    {
        return [
            'assessment_details.*.score' => 'score',
            'assessment_details.*.category_uuid' => 'category',
        ];
    }
}
