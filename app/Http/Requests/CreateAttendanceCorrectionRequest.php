<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateAttendanceCorrectionRequest
 *
 * Request class untuk menangani validasi pengajuan koreksi absensi (Attendance Correction) karyawan.
 */
class CreateAttendanceCorrectionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'attendance_id' => ['required', 'exists:attendances,id'],
            'clock_in_requested' => ['sometimes', 'date_format:H:i'],
            'clock_out_requested' => ['sometimes', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        return $rules;
    }

    /**
     * Mengonfigurasi instance validator untuk logika validasi tambahan setelah aturan utama.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $attendance = \App\Models\Attendance::find($this->attendance_id);
            $employee = $attendance?->employee;

            if (! $employee) {
                $validator->errors()->add('attendance_id', 'The selected attendance record is invalid or has no associated employee.');

                return;
            }

            $this->merge([
                'employee_id' => $employee->id,
            ]);
        });

    }
}
