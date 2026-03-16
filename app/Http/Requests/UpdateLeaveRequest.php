<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateLeaveRequest
 *
 * Request class untuk menangani validasi pembaruan pengajuan cuti (Leave) karyawan.
 */
class UpdateLeaveRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna memiliki izin untuk membuat request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Policy handle authorization
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk rentang tanggal, alasan, status setengah hari, dan lampiran
     */
    public function rules(): array
    {
        return [
            'date_start'   => ['required', 'date'],
            'date_end'     => ['required', 'date', 'after_or_equal:date_start'],
            'reason'       => ['required', 'string', 'max:500'],
            'is_half_day'  => ['nullable', 'boolean'],
            'attachment'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
