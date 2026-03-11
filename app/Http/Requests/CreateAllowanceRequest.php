<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAllowanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Aturan validasi untuk nama, tipe, dan jumlah tunjangan
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => 'required|string|in:fixed,percentage',
            'amount' => 'required|numeric|decimal:0,2',
        ];
    }
}
