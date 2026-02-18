<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handle authorization
    }

    public function rules(): array
    {
        return [
            'date_start'   => ['required', 'date'],
            'date_end'     => ['required', 'date', 'after_or_equal:date_start'],
            'reason'       => ['required', 'string', 'max:500'],
            'is_half_day'  => ['nullable', 'boolean'],
            'attachment'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
