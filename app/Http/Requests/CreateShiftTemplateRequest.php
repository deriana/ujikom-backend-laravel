<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateShiftTemplateRequest
 *
 * Request class untuk menangani validasi pembuatan template shift kerja baru.
 */
class CreateShiftTemplateRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna memiliki izin untuk membuat request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menyiapkan data untuk validasi dengan mengonversi input cross_day menjadi boolean.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cross_day' => filter_var($this->cross_day, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:shift_templates,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|different:start_time',
            'cross_day' => 'required|boolean',
            'late_tolerance_minutes' => 'nullable|integer|min:0|max:180',
        ];
    }

    /**
     * Mengonfigurasi instance validator untuk logika validasi tambahan setelah aturan utama.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->input('start_time');
            $end = $this->input('end_time');
            $crossDay = $this->boolean('cross_day');

            if (!$crossDay && $end <= $start) {
                $validator->errors()->add(
                    'end_time',
                    'End time must be after start time when cross_day is false.'
                );
            }
        });
    }
}
