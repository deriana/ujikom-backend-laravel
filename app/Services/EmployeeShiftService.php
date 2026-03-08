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
            // 1. Validate if the date is a valid workday (not a holiday or weekend)
            if (! $this->workdayService->isWorkday(Carbon::parse($data['shift_date']))) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 2. Retrieve employee and shift template (Select only needed columns)
            $employee = Employee::select('id', 'nik', 'user_id')
                ->with('user:id,name')
                ->where('nik', $data['employee_nik'])
                ->firstOrFail();

            $template = ShiftTemplate::select('id', 'uuid', 'name')
                ->where('uuid', $data['shift_template_uuid'])
                ->firstOrFail();

            // 3. Use updateOrCreate to prevent duplicates and reduce queries
            $shift = EmployeeShift::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'shift_date' => $data['shift_date'],
                ],
                ['shift_template_id' => $template->id]
            );

            // 4. Set custom notification data
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
            // 1. Validate the new shift date
            if (! $this->workdayService->isWorkday(Carbon::parse($data['shift_date']))) {
                throw new \Exception("Failed: Date {$data['shift_date']} is a holiday or weekend.");
            }

            // 2. Find the requested shift template
            $template = ShiftTemplate::select('id', 'name')->where('uuid', $data['shift_template_uuid'])->firstOrFail();

            // 3. Set custom notification data
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
