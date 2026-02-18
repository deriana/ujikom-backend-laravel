<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'manual_adjustment' => ['nullable', 'numeric'],
            'adjustment_note'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
