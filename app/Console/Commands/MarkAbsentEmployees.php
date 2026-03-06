<?php

namespace App\Console\Commands;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert absent attendance records for employees with no attendance after work end time';

    /**
     * Execute the console command.
     */
    public function handle(WorkdayService $workdayService): int
    {
        // Fetch attendance configuration from settings table
        $setting = Setting::where('key', 'attendance')->first()?->values;

        if (! $setting) {
            $this->error('Attendance setting not found.');
            return self::FAILURE;
        }

        $today = Carbon::today();

        // Check if today is a valid workday (not a weekend or holiday)
        if (! $workdayService->isWorkday($today)) {
            $this->info('Today is not a workday. No absent generated.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $workEnd = Carbon::createFromFormat('H:i', $setting['work_end_time'])
            ->setDateFrom($today);

        // Only run this command after the official work hours have ended
        if ($now->lt($workEnd)) {
            $this->info('Work end time has not passed yet.');
            return self::SUCCESS;
        }

        // Fetch employees who haven't clocked in and are not on approved leave
        $employees = Employee::query()
            // Filter: Exclude Owner and Director roles from mandatory attendance
            ->whereHas('user', function ($q) {
                $q->withoutRole([
                    \App\Enums\UserRole::OWNER->value,
                    \App\Enums\UserRole::DIRECTOR->value
                ]);
            })
            // Check if employee has no attendance record for today
            ->whereDoesntHave('attendances', function ($q) use ($today) {
                $q->whereDate('date', $today);
            })
            // Check if employee is not currently on an approved leave
            ->whereDoesntHave('leaves', function ($q) use ($today) {
                $q->where('approval_status', ApprovalStatus::APPROVED->value)
                    ->whereDate('date_start', '<=', $today)
                    ->whereDate('date_end', '>=', $today);
            })
            ->get();

        // Create 'absent' records for the identified employees
        foreach ($employees as $employee) {
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'status' => 'absent',
                // Generate UUID for the attendance record
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }

        $this->info("Inserted {$employees->count()} absent records (Skipped Owner & Director).");

        return self::SUCCESS;
    }
}
