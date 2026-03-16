<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePayrollRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk bulan dan daftar NIK karyawan
     */
    public function rules(): array
    {
        return [
            'month' => 'required|date_format:Y-m',
            'employee_niks' => 'required|array',
            'employee_niks.*' => 'exists:employees,nik',
        ];
    }
}
