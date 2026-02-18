<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class CreateOvertimeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'reason' => ['required', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
        ];

        $user = $this->user();

        if ($user->hasAnyRole([UserRole::ADMIN, UserRole::HR])) {
            $rules['employee_nik'] = ['nullable', 'exists:employees,nik'];
        } else {
            $rules['employee_nik'] = ['prohibited'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $user = $this->user();

            // 1. Cari Employee (Pola EarlyLeave)
            if ($user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) && $this->employee_nik) {
                $employee = Employee::where('nik', $this->employee_nik)->first();
            } else {
                $employee = $user->employee;
            }

            if (! $employee) {
                $validator->errors()->add('employee_nik', 'Employee data not found.');

                return;
            }

            // 2. Cari Attendance ID secara otomatis berdasarkan tanggal
            // Jika input 'date' kosong, gunakan tanggal hari ini
            $targetDate = $this->date ?: now()->toDateString();

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $targetDate)
                ->first();

            if (! $attendance) {
                $validator->errors()->add('date', 'Attendance data not found for date '.$targetDate);

                return;
            }

            // 3. Merge Keduanya ke dalam request
            // Jadi di Controller tinggal pakai $request->attendance_id & $request->employee_id
            $this->merge([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
            ]);
        });
    }
}
