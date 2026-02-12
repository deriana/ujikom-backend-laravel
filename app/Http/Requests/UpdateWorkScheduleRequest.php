<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scheduleId = $this->route('work_schedule');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('work_schedules', 'name')->ignore($scheduleId),
            ],
            'work_mode_id' => 'required|exists:work_modes,id',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i|after:work_start_time',
            'requires_office_location' => 'required|boolean',
        ];
    }
}
