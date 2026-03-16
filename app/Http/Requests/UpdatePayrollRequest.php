<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdatePayrollRequest
 *
 * Request class untuk menangani validasi pembaruan data payroll (penggajian),
 * khususnya untuk penyesuaian manual dan catatan terkait.
 */
class UpdatePayrollRequest extends FormRequest
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
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk penyesuaian manual dan catatan
     */
    public function rules(): array
    {
        return [
            'manual_adjustment' => ['nullable', 'numeric'],
            'adjustment_note'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
