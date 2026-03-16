<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeShiftRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna memiliki izin untuk membuat request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_nik' => 'required|exists:employees,nik',
            'shift_template_uuid' => 'required|exists:shift_templates,uuid',
            'shift_date' => [
                'required',
                'date',
                Rule::unique('employee_shifts')
                    ->where(function ($query) {
                        $employeeId = \App\Models\Employee::where('nik', $this->employee_nik)
                            ->value('id');
                        return $query->where('employee_id', $employeeId);
                    }),
            ],
        ];
    }
}
