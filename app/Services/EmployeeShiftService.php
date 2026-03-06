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

    /**
     * Get all employee shifts with their related employee and shift template.
     */
    public function index()
    {
        // 1. Retrieve all shifts with eager loaded relationships
        return EmployeeShift::with(['employee', 'shiftTemplate'])
            ->latest()
            ->get();
    }

    /**
     * Assign a new shift to an employee.
     */
    public function store(array $data): EmployeeShift
    {
        return DB::transaction(function () use ($data) {
            // 1. Parse the requested shift date
            $date = Carbon::parse($data['shift_date']);

            // 2. Validate if the date is a valid workday (not a holiday or weekend)
            $isWorkday = $this->workdayService->isWorkday($date);
            if (! $isWorkday) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 3. Retrieve employee and shift template by their identifiers
            $employee = Employee::where('nik', $data['employee_nik'])->firstOrFail();
            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // 4. Prevent duplicate shift assignments for the same employee on the same date
            $exists = EmployeeShift::where('employee_id', $employee->id)
                ->where('shift_date', $data['shift_date'])
                ->exists();
            if ($exists) {
                throw new \Exception('Employee already has a shift assigned on this date.');
            }

            // 5. Create the shift record
            $shift = new EmployeeShift([
                'employee_id' => $employee->id,
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            // 6. Set custom notification data
            $shift->customNotification = [
                'title' => 'Shift Assigned',
                'message' => "A new shift has been assigned to {$employee->user->name} on {$data['shift_date']} with template {$template->name}.",
            ];

            $shift->save();

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    /**
     * Update an existing employee shift assignment.
     */
    public function update(EmployeeShift $shift, array $data): EmployeeShift
    {
        return DB::transaction(function () use ($shift, $data) {
            // 1. Parse and validate the new shift date
            $date = Carbon::parse($data['shift_date']);
            $isWorkday = $this->workdayService->isWorkday($date);
            if (! $isWorkday) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 2. Find the requested shift template
            $template = ShiftTemplate::where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // 3. Set custom notification data for the update
            $shift->customNotification = [
                'title' => 'Shift Updated',
                'message' => "Shift schedule for {$shift->employee->user->name} has been updated to {$data['shift_date']} with template {$template->name}.",
            ];

            // 4. Update the shift record
            $shift->update([
                'shift_template_id' => $template->id,
                'shift_date' => $data['shift_date'],
            ]);

            return $shift->load(['employee', 'shiftTemplate']);
        });
    }

    /**
     * Delete an employee shift assignment.
     */
    public function delete(EmployeeShift $shift): bool
    {
        return DB::transaction(function () use ($shift) {
            // 1. Set custom notification data before deletion
            $shift->customNotification = [
                'title' => 'Shift Deleted',
                'message' => "Shift schedule for {$shift->employee->user->name} on {$shift->shift_date->format('Y-m-d')} has been removed.",
            ];

            // 2. Delete the shift record
            return $shift->delete();
        });
    }

    /**
     * Show details of a specific employee shift.
     */
    public function show(EmployeeShift $shift): EmployeeShift
    {
        // 1. Load related employee and shift template
        return $shift->load(['employee', 'shiftTemplate']);
    }
}
