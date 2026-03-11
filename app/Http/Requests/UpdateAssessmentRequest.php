<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string',
            'assessment_details' => 'required|array|min:1',
            'assessment_details.*.category_uuid' => 'required|exists:assessment_categories,uuid',
            'assessment_details.*.score' => 'required|integer|min:1|max:5',
            'assessment_details.*.bonus_salary' => 'nullable|numeric',
        ];
    }
}
