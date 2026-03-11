<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdatePositionRequest
 *
 * Request class untuk menangani validasi pembaruan data jabatan (Position).
 */
class UpdatePositionRequest extends FormRequest
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
