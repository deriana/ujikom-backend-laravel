<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class CreateEarlyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            UserRole::ADMIN,
            UserRole::HR,
            UserRole::MANAGER,
            UserRole::EMPLOYEE,
            UserRole::DIRECTOR,
            UserRole::OWNER,
            UserRole::FINANCE,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        $user = $this->user();

        // ADMIN / HR → boleh isi employee_nik
        if ($user->hasAnyRole([UserRole::ADMIN, UserRole::HR])) {
            $rules['employee_nik'] = ['required', 'exists:employees,nik'];
        }

        // MANAGER → wajib isi, tapi akan divalidasi sebagai bawahan
        elseif ($user->hasRole(UserRole::MANAGER)) {
            $rules['employee_nik'] = ['required', 'exists:employees,nik'];
        }

        // EMPLOYEE → dilarang kirim employee_nik
        else {
            $rules['employee_nik'] = ['prohibited'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($validator->errors()->any()) {
                return;
            }

            $user = $this->user();

            // EMPLOYEE → otomatis dirinya sendiri
            if ($user->hasRole(UserRole::EMPLOYEE)) {
                $this->merge([
                    'employee_id' => $user->employee?->id,
                ]);

                return;
            }

            $employee = Employee::where('nik', $this->employee_nik)->first();

            if (! $employee) {
                $validator->errors()->add('employee_nik', 'Employee tidak ditemukan.');

                return;
            }

            // MANAGER → hanya boleh bawahan langsung
            if ($user->hasRole(UserRole::MANAGER)) {
                if ($employee->manager_id !== $user->employee?->id) {
                    $validator->errors()->add(
                        'employee_nik',
                        'Manager hanya dapat mengajukan early leave untuk bawahan langsung.'
                    );

                    return;
                }
            }

            $this->merge([
                'employee_id' => $employee->id,
            ]);
        });
    }
}
