<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class LoginRequest
 *
 * Request class untuk menangani validasi proses masuk (login) pengguna ke dalam sistem.
 */
class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk email dan password
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email terdaftar karyawan.',
                'example' => 'sakiko@app.com',
            ],
            'password' => [
                'description' => 'Password akun.',
                'example' => 'mbg(myBiniGweh)',
            ],
        ];
    }
}
