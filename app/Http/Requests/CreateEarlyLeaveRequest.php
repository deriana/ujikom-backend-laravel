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

        if ($user->hasAnyRole([UserRole::ADMIN, UserRole::HR])) {
            $rules['employee_nik'] = ['nullable', 'exists:employees,nik'];
        }
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
