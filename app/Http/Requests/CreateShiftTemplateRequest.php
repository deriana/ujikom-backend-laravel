<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cross_day' => filter_var($this->cross_day, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:shift_templates,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|different:start_time',
            'cross_day' => 'required|boolean',
            'late_tolerance_minutes' => 'nullable|integer|min:0|max:180',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->input('start_time');
            $end = $this->input('end_time');
            $crossDay = $this->boolean('cross_day');

            if (!$crossDay && $end <= $start) {
                $validator->errors()->add(
                    'end_time',
                    'End time must be after start time when cross_day is false.'
                );
            }
        });
    }
}
