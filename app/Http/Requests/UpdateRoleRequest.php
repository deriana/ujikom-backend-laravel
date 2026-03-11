<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama peran dan daftar izin
     */
    public function rules(): array
    {
        $roleId = $this->route('role')->id;

        return [
            'name' => 'required|string|unique:roles,name,'.$roleId, // Nama peran harus unik kecuali untuk peran ini sendiri
            'permissions' => 'array', // Daftar izin harus berupa array
            'permissions.*' => 'integer|exists:permissions,id', // Setiap ID izin harus ada di tabel permissions
        ];
    }
}
