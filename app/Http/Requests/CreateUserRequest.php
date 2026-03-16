<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk data pengguna dan karyawan baru
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            // 'password' => 'required|string|min:8',
            // 'password_confirmation' => 'required|same:password',
            'team_uuid' => 'required|string|exists:teams,uuid',
            'role' => ['nullable', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => 'required|boolean',
            'position_uuid' => 'required|string|exists:positions,uuid',
            'manager_nik' => 'nullable|string|exists:employees,nik',
            'employee_status' => 'required|integer',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0|decimal:0,2',
            'phone' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
        ];
    }
}
