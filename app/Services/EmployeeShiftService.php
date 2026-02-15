<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            Log::info($isWorkday);
            // dd($isWorkday);

            if (! $isWorkday) {
                // Jika isWorkday = false, kita STOP di sini
                throw new \Exception("Gagal: Tanggal {$data['shift_date']} adalah hari libur (Holiday/Weekend).");
            }

            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // Opsional: Cek apakah sudah ada shift di tanggal tersebut (mencegah duplikat)
            $exists = EmployeeShift::where('employee_id', $employee->id)
                ->where('shift_date', $data['shift_date'])
                ->exists();

            if ($exists) {
                throw new \Exception('Employee sudah memiliki shift pada tanggal tersebut.');
            }

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

            // Validasi hari kerja saat update
            if (! $this->workdayService->isWorkday($date)) {
                throw new \Exception('Perubahan shift gagal. Tanggal terpilih adalah hari libur/weekend.');
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
