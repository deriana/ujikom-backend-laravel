<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDivisionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama, kode divisi, dan daftar tim
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'code' => 'sometimes|required|string|max:10|unique:divisions,code,'.$this->division->id,
            'teams' => 'nullable|array',
            'teams.*.uuid' => 'nullable|exists:teams,uuid',
            'teams.*.name' => 'required|string|max:255',
        ];
    }
}
