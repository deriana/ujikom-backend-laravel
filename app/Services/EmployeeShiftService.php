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
            // TESTING
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
            $date = Carbon::parse($data['shift_date']);

            $isWorkday = $this->workdayService->isWorkday($date);

            if (! $isWorkday) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

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

            $shift->delete();

            return true;
        });
    }

    public function show(EmployeeShift $shift): EmployeeShift
    {
        return $shift->load(['employee', 'shiftTemplate']);
    }
}
