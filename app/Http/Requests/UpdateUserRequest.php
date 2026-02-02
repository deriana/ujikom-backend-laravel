<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => 'sometimes|required|string',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'team_uuid' => 'sometimes|required|string|exists:teams,uuid',
            'role_uuid' => 'sometimes|required|string|exists:roles,uuid',
            'is_active' => 'sometimes|required|boolean',
            'position_uuid' => 'sometimes|required|string|exists:positions,uuid',
            'manager_nik' => 'nullable|string|exists:employees,nik',
            'employee_status' => 'required|integer',
            'contract_start' => 'nullable|date|required_if:employee_status,contract,intern,probation',
            'contract_end' => 'nullable|date|after_or_equal:contract_start|required_if:employee_status,contract,intern,probation',
            'base_salary' => 'nullable|numeric|min:0|decimal:0,2',
            'phone' => [
                'nullable',
                'string',
                Rule::unique('employees', 'phone')
                    ->ignore(optional($this->route('user')->employee)->id),
            ],
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'resign_date' => 'nullable|date|after_or_equal:join_date',
        ];
    }
}
