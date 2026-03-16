<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeWorkScheduleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk NIK karyawan, UUID jadwal kerja, dan rentang tanggal
     */
    public function rules(): array
    {
        return [
            'employee_nik' => 'required|exists:employees,nik',
            'work_schedule_uuid' => 'required|exists:work_schedules,uuid',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
