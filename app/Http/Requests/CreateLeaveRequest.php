<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CreateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Menggunakan standard Laravel 'hasAnyRole' jika tersedia di model User
        return $this->user()->hasAnyRole([
            UserRole::ADMIN,
            UserRole::HR,
            UserRole::MANAGER,
            UserRole::EMPLOYEE,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'leave_type_uuid' => ['required', 'uuid', 'exists:leave_types,uuid'],
            'date_start' => ['required', 'date', 'after_or_equal:today'], // Opsional: cegah backdate
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'is_half_day' => ['nullable', 'boolean'],
        ];

        // Logika Kondisional Role
        if ($this->user()->hasAnyRole([UserRole::ADMIN, UserRole::HR])) {
            $rules['employee_nik'] = ['sometimes', 'exists:employees,nik'];
        } else {
            $rules['employee_nik'] = ['prohibited'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            Log::info('CreateLeaveRequest: masuk after validator');

            if ($validator->errors()->any()) {
                Log::warning('CreateLeaveRequest: sudah ada error awal', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return;
            }

            // 1️⃣ Leave Type
            $leaveType = LeaveType::where('uuid', $this->leave_type_uuid)->first();

            Log::info('LeaveType lookup', [
                'uuid' => $this->leave_type_uuid,
                'found' => (bool) $leaveType,
                'is_active' => $leaveType?->is_active,
            ]);

            if (! $leaveType || ! $leaveType->is_active) {
                $validator->errors()->add('leave_type_uuid', 'Jenis cuti tidak tersedia atau tidak aktif.');
                Log::warning('LeaveType invalid');

                return;
            }

            // 2️⃣ Half Day Validation
            if ($this->is_half_day && $this->date_start !== $this->date_end) {
                $validator->errors()->add('is_half_day', 'Cuti setengah hari harus pada tanggal yang sama.');
                Log::warning('Half day invalid range');

                return;
            }

            // 3️⃣ Hitung Durasi
            $daysRequested = $this->calculateWorkDays(
                $this->date_start,
                $this->date_end,
                $this->is_half_day
            );

            Log::info('Durasi dihitung', [
                'days_requested' => $daysRequested,
            ]);

            // 4️⃣ Resolve Employee
            $employee = $this->input('employee_nik')
                ? \App\Models\Employee::where('nik', $this->input('employee_nik'))->first()
                : $this->user()->employee;

            Log::info('Employee resolved', [
                'employee_nik_input' => $this->input('employee_nik'),
                'user_employee_id' => optional($this->user()->employee)->id,
                'resolved_employee_id' => optional($employee)->id,
            ]);

            if (! $employee) {
                $validator->errors()->add('employee_nik', 'Employee tidak ditemukan.');
                Log::warning('Employee null - request dihentikan');

                return;
            }

            // 5️⃣ Cek Saldo
            if ($leaveType->default_days !== null) {

                $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->first();

                $remaining = $balance ? $balance->remaining_days : 0;

                Log::info('Balance check', [
                    'remaining' => $remaining,
                    'requested' => $daysRequested,
                ]);

                if ($daysRequested > $remaining) {
                    $validator->errors()->add(
                        'date_end',
                        "Saldo tidak cukup. Sisa: $remaining hari, Diminta: $daysRequested hari."
                    );
                    Log::warning('Saldo tidak cukup');

                    return;
                }
            }

            // Merge debug
            Log::info('Merge data ke request', [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'duration' => $daysRequested,
            ]);

            $this->merge([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'duration' => $daysRequested,
            ]);
        });
    }

    /**
     * Menghitung hari kerja (Senin-Jumat)
     */
    private function calculateWorkDays($start, $end, $isHalfDay = false): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        // Versi simpel: diffInDays + 1
        // Versi Pro: Hanya hitung hari kerja (exclude weekend)
        return (float) $startDate->diffInDaysFiltered(function (Carbon $date) {
            return ! $date->isWeekend();
        }, $endDate) + 1;
    }
}
