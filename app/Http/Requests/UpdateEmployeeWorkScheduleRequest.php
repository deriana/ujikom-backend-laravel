<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateEmployeeWorkScheduleRequest
 *
 * Request class untuk menangani validasi pembaruan jadwal kerja karyawan (Employee Work Schedule).
 */
class UpdateEmployeeWorkScheduleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk UUID jadwal kerja dan rentang tanggal
     */
    public function rules(): array
    {
        return [
            'work_schedule_uuid' => 'required|exists:work_schedules,uuid',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
