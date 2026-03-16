<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateEarlyLeaveRequest
 *
 * Request class untuk menangani validasi pengajuan izin pulang awal (Early Leave) karyawan.
 */
class CreateEarlyLeaveRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        if ($this->user()->hasRole(UserRole::ADMIN->value)) {
            $rules['employee_nik'] = ['required', 'exists:employees,nik'];
        } elseif ($this->user()->hasRole(UserRole::HR->value)) {
            $rules['employee_nik'] = ['sometimes', 'exists:employees,nik'];
        } else {
            $rules['employee_nik'] = ['prohibited'];
        }

        return $rules;
    }

    /**
     * Mengonfigurasi instance validator untuk logika validasi tambahan setelah aturan utama.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $user = $this->user();

            if ($user->hasAnyRole([UserRole::ADMIN, UserRole::HR])) {
                $employee = \App\Models\Employee::where('nik', $this->employee_nik)->first();
            } else {
                $employee = $user->employee;
            }

            if (! $employee) {
                $validator->errors()->add('employee_nik', 'Employee data not found or your account is not linked to an employee record.');

                return;
            }

            $this->merge([
                'employee_id' => $employee->id,
            ]);
        });

    }
}
