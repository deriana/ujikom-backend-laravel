<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePositionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama, gaji pokok, dan daftar tunjangan
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'base_salary' => 'required|numeric|min:0|decimal:0,2',
            'allowances' => 'nullable|array',
            'allowances.*.uuid' => 'nullable|exists:allowances,uuid',
            'allowances.*.amount' => 'nullable|numeric|decimal:0,2',
        ];
    }
}
