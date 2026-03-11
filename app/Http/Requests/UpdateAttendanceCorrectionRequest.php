<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateAttendanceCorrectionRequest
 *
 * Request class untuk menangani validasi pembaruan pengajuan koreksi absensi (Attendance Correction).
 */
class UpdateAttendanceCorrectionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk alasan, lampiran, dan waktu jam masuk/keluar yang diminta
     */
    public function rules(): array
    {
        return [
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'clock_in_requested' => 'nullable|date_format:H:i',
            'clock_out_requested' => 'nullable|date_format:H:i',
        ];
    }
}
