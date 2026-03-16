<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class CreateAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_id'       => ['nullable', 'exists:attendances,id'],
            'employee_id'         => [
                Auth::user()->hasAnyRole(['admin', 'hr']) ? 'required_without:attendance_id' : 'nullable',
                'exists:employees,id'
            ],
            'date'                => ['required_without:attendance_id', 'nullable', 'date'],
            'clock_in_requested'  => ['required', 'date_format:H:i'],
            'clock_out_requested' => ['required', 'date_format:H:i', 'after:clock_in_requested'],
            'reason'              => ['required', 'string', 'max:500'],
            'attachment'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $user = Auth::user();
            $finalEmployeeId = null;
            $date = $this->date;

            // --- 1. PENENTUAN EMPLOYEE ID ---
            if ($this->attendance_id) {
                // Kasus Koreksi: Ambil Employee dari record absensi yang sudah ada
                $attendance = Attendance::find($this->attendance_id);
                $finalEmployeeId = $attendance->employee_id;
                $date = $attendance->date->format('Y-m-d');

                // Security: Jika bukan Admin/HR, dilarang koreksi punya orang lain
                if (!$user->hasAnyRole(['admin', 'hr']) && $attendance->employee_id !== $user->employee_id) {
                    $validator->errors()->add('attendance_id', 'Anda tidak berwenang mengoreksi data ini.');
                    return;
                }
            } else {
                // Kasus Manual Input:
                if ($user->hasAnyRole(['admin', 'hr'])) {
                    // Admin/HR mengambil ID dari dropdown/input form
                    $finalEmployeeId = $this->employee_id;
                } else {
                    // User biasa dipaksa pakai ID sendiri
                    $finalEmployeeId = $user->employee_id;
                }
            }

            if (!$finalEmployeeId) {
                $validator->errors()->add('employee_id', 'Data karyawan tidak ditemukan.');
                return;
            }

            // --- 2. MERGE DATA UNTUK CONTROLLER ---
            $this->merge([
                'employee_id' => $finalEmployeeId,
                'clock_in_full' => $date . ' ' . $this->clock_in_requested,
                'clock_out_full' => $date . ' ' . $this->clock_out_requested,
            ]);
        });
    }
}
