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
    protected $signature = 'attendance:mark-absent';

    protected $description = 'Insert absent attendance records for employees with no attendance after work end time';

    public function handle(WorkdayService $workdayService): int
    {
        $setting = Setting::where('key', 'attendance')->first()?->values;

        if (! $setting) {
            $this->error('Attendance setting not found.');

            return self::FAILURE;
        }

        $today = Carbon::today();

        if (! $workdayService->isWorkday($today)) {
            $this->info('Today is not a workday. No absent generated.');

            return self::SUCCESS;
        }

        $now = Carbon::now();

        $workEnd = Carbon::createFromFormat('H:i', $setting['work_end_time'])
            ->setDateFrom($today);

        if ($now->lt($workEnd)) {
            $this->info('Work end time has not passed yet.');

            return self::SUCCESS;
        }

        $employees = Employee::whereDoesntHave('attendances', function ($q) use ($today) {
            $q->whereDate('date', $today);
        })
            ->whereDoesntHave('leaves', function ($q) use ($today) {
                $q->where('approval_status', ApprovalStatus::APPROVED->value)
                    ->whereDate('date_start', '<=', $today)
                    ->whereDate('date_end', '>=', $today);
            })
            ->get();

        foreach ($employees as $employee) {
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'status' => 'absent',
            ]);
        }

        $this->info("Inserted {$employees->count()} absent records.");

        return self::SUCCESS;
    }
}
