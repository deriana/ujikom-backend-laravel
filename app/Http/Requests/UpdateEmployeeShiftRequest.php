<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeShiftRequest extends FormRequest
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
        $shift = $this->route('employee_shift'); // model instance

        return [
            'employee_nik' => 'required|exists:employees,nik',
            'shift_template_uuid' => 'required|exists:shift_templates,uuid',
            'shift_date' => [
                'required',
                'date',
                Rule::unique('employee_shifts')
                    ->ignore($shift?->id)
                    ->where(function ($query) {
                        $employeeId = \App\Models\Employee::where('nik', $this->employee_nik)
                            ->value('id');

                        return $query->where('employee_id', $employeeId);
                    }),
            ],
        ];
    }
}
