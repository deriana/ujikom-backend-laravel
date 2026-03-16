<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSubmissionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk alasan, tipe permintaan, template shift, jadwal kerja, dan rentang tanggal
     */
    public function rules(): array
    {
        return [
            'reason' => 'required|string',
            'request_type' => 'required|in:SHIFT,WORK_MODE',
            'shift_template_uuid' => 'nullable|exists:shift_templates,uuid',
            'work_schedule_uuid' => 'nullable|exists:work_schedules,uuid',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
