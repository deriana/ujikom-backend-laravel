<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftTemplateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama, waktu mulai, waktu selesai, dan toleransi keterlambatan
     */
    public function rules(): array
    {
        $id = $this->route('shift_template');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shift_templates', 'name')->ignore($id),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|different:start_time',
            'cross_day' => 'required|boolean',
            'late_tolerance_minutes' => 'nullable|integer|min:0|max:180',
        ];
    }
}
