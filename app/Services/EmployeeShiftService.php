<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeShiftService
{
    protected WorkdayService $workdayService;

    public function __construct(WorkdayService $workdayService)
    {
        $this->workdayService = $workdayService;
    }

    public function index()
    {
        return EmployeeShift::with(['employee', 'shiftTemplate'])
            ->latest()
            ->get();
    }

    public function store(array $data): EmployeeShift
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['shift_date']);

            $isWorkday = $this->workdayService->isWorkday($date);

            if (! $isWorkday) {
                // Jika isWorkday = false, kita STOP di sini
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // Opsional: Cek apakah sudah ada shift di tanggal tersebut (mencegah duplikat)
            $exists = EmployeeShift::where('employee_id', $employee->id)
                ->where('shift_date', $data['shift_date'])
                ->exists();

            if ($exists) {
                throw new \Exception('Employee already has a shift assigned on this date.');
            }

            $shift = new EmployeeShift([
                'employee_id' => $employee->id,
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            $shift->customNotification = [
                'title' => 'Shift Assigned',
                'message' => "A new shift has been assigned to {$employee->user->name} on {$data['shift_date']} with template {$template->name}.",
            ];

            $shift->save();

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

   public function update(EmployeeShift $shift, array $data): EmployeeShift
    {
        return DB::transaction(function () use ($shift, $data) {
            $date = Carbon::parse($data['shift_date']);

            $isWorkday = $this->workdayService->isWorkday($date);
            if (! $isWorkday) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

            $shift->customNotification = [
                'title' => 'Shift Updated',
                'message' => "Shift schedule for {$shift->employee->user->name} has been updated to {$data['shift_date']} with template {$template->name}.",
            ];

            $shift->update([
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    public function delete(EmployeeShift $shift): bool
    {
        return DB::transaction(function () use ($shift) {
            $shift->customNotification = [
                'title' => 'Shift Deleted',
                'message' => "Shift schedule for {$shift->employee->user->name} on {$shift->shift_date->format('Y-m-d')} has been removed.",
            ];

            return $shift->delete();
        });
    }
    public function show(EmployeeShift $shift): EmployeeShift
    {
        return $shift->load(['employee', 'shiftTemplate']);
    }
}
