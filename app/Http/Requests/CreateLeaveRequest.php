<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateLeaveRequest
 *
 * Request class untuk menangani validasi pengajuan cuti (Leave) karyawan.
 */
 class CreateLeaveRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna memiliki izin untuk membuat request ini.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'leave_type_uuid' => ['required', 'uuid', 'exists:leave_types,uuid'],
            'date_start' => ['required', 'date', 'after_or_equal:today'],
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

    /**
     * Mengonfigurasi instance validator untuk logika validasi tambahan setelah aturan utama.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($validator->errors()->any()) {
                return;
            }

            // 1️⃣ Leave Type
            $leaveType = LeaveType::where('uuid', $this->leave_type_uuid)->first();

            // Log::info('LeaveType lookup', [
            //     'uuid' => $this->leave_type_uuid,
            //     'found' => (bool) $leaveType,
            //     'is_active' => $leaveType?->is_active,
            // ]);

            if (! $leaveType || ! $leaveType->is_active) {
                $validator->errors()->add('leave_type_uuid', 'The selected leave type is unavailable or inactive.');
                // Log::warning('LeaveType invalid');

                return;
            }

            // 2️⃣ Half Day Validation
            if ($this->is_half_day && $this->date_start !== $this->date_end) {
                $validator->errors()->add('is_half_day', 'Half-day leave must be on the same date.');
                // Log::warning('Half day invalid range');

                return;
            }

            // 3️⃣ Hitung Durasi
            $workdayService = app(\App\Services\WorkdayService::class);

            $daysRequested = $this->calculateWorkDays(
                $this->date_start,
                $this->date_end,
                (bool) $this->is_half_day,
                $workdayService
            );

            if ($daysRequested === 0) {
                $validator->errors()->add(
                    'date_start',
                    'Leave cannot be requested on non-working days (weekends or holidays).'
                );

                return;
            }

            // Log::info('Durasi dihitung', [
            //     'days_requested' => $daysRequested,
            // ]);

            // 4️⃣ Resolve Employee
            $employee = $this->input('employee_nik')
                ? \App\Models\Employee::where('nik', $this->input('employee_nik'))->first()
                : $this->user()->employee;

            if (! $employee) {
                $validator->errors()->add('employee_nik', 'Employee not found.');

                return;
            }

            // 5️⃣ Cek Saldo
            // if ($leaveType->default_days !== null) {

            //     $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            //         ->where('leave_type_id', $leaveType->id)
            //         ->first();

            //     $remaining = $balance ? $balance->remaining_days : 0;

            //     if ($daysRequested > $remaining) {
            //         $validator->errors()->add(
            //             'date_end',
            //             "Insufficient balance. Remaining: $remaining days, Requested: $daysRequested days."
            //         );

            //         return;
            //     }
            // }

            $this->merge([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'duration' => $daysRequested,
            ]);
        });
    }

    /**
     * Menghitung jumlah hari kerja dalam rentang tanggal yang diajukan.
     *
     * @param string $start Tanggal mulai
     * @param string $end Tanggal selesai
     * @param bool $isHalfDay Status apakah cuti setengah hari
     * @param WorkdayService $workdayService Service untuk mengecek hari kerja
     * @return float Jumlah hari kerja yang dihitung
     */
    private function calculateWorkDays(
        string $start,
        string $end,
        bool $isHalfDay,
        WorkdayService $workdayService
    ): float {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($startDate->gt($endDate)) {
            return 0;
        }

        if ($isHalfDay) {
            return $workdayService->isWorkday($startDate) ? 0.5 : 0;
        }

        $days = 0;
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $check = $workdayService->isWorkday($date);
            // Log::info('Checking date: '.$date->toDateString().' - Is Workday: '.($check ? 'YES' : 'NO'));

            if ($check) {
                $days++;
            }
        }

        return (float) $days;
    }
}
