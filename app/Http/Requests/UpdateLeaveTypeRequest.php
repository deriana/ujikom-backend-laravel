<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateLeaveTypeRequest
 *
 * Request class untuk menangani validasi pembaruan tipe cuti (Leave Type).
 */
class UpdateLeaveTypeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama, status aktif, jumlah hari default, gender, dan status keluarga
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'is_active' => 'required|boolean',
            'default_days' => 'nullable|integer',
            'gender' => 'nullable|in:male,female,all',
            'requires_family_status' => 'nullable|boolean',
        ];
    }
}
