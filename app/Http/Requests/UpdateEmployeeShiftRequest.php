<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeShiftRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk template shift dan tanggal shift
     */
    public function rules(): array
    {
        $shift = $this->route('employee_shift'); // instansi model

        return [
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
