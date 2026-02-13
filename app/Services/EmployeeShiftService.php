<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use Illuminate\Support\Facades\DB;

class EmployeeShiftService
{
    public function index()
    {
        return EmployeeShift::with(['employee', 'shiftTemplate'])
            ->latest()
            ->get();
    }

    public function store(array $data): EmployeeShift
    {
        return DB::transaction(function () use ($data) {

            $employee = Employee::where('nik', $data['employee_nik'])
                ->firstOrFail();

            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])
                ->firstOrFail();

            return EmployeeShift::create([
                'employee_id' => $employee->id,
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ])->load(['employee', 'shiftTemplate']);
        });
    }

    public function update(EmployeeShift $shift, array $data): EmployeeShift
    {
        return DB::transaction(function () use ($shift, $data) {

            $employee = Employee::where('nik', $data['employee_nik'])
                ->firstOrFail();

            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])
                ->firstOrFail();

            $shift->update([
                'employee_id' => $employee->id,
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    public function delete(EmployeeShift $shift): bool
    {
        return DB::transaction(function () use ($shift) {

            $shift->delete();

            return true;
        });
    }

    public function show(EmployeeShift $shift): EmployeeShift
    {
        return $shift->load(['employee', 'shiftTemplate']);
    }
}
