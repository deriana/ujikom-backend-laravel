<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeShiftRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'shift_date' => [
                'required',
                'date',
                Rule::unique('employee_shifts')
                    ->where(fn ($q) => $q->where('employee_id', $this->employee_id)
                    ),
            ],
        ];
    }
}
