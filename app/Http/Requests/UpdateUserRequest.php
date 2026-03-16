<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateUserRequest
 *
 * Request class untuk menangani validasi pembaruan data pengguna (User) dan data karyawan (Employee) terkait.
 */
class UpdateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk data akun, peran, jabatan, status kontrak, dan informasi pribadi
     */
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
            'role' => ['nullable', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
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
