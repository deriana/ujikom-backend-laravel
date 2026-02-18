<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAttendanceSubmissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'request_type' => ['required', 'in:SHIFT,WORK_MODE'],
            'shift_template_uuid' => 'nullable|exists:shift_templates,uuid',
            'work_schedule_uuid' => 'nullable|exists:work_schedules,uuid',
            'start_date' => ['required', 'date'],
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'employee_nik' => ['prohibited'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($validator->errors()->any()) {
                return;
            }

            $user = $this->user();

            if (! $user->employee) {
                $validator->errors()->add(
                    'employee',
                    'User is not linked to an employee.'
                );

                return;
            }

            $this->merge([
                'employee_id' => $user->employee->id,
            ]);
        });
    }
}
