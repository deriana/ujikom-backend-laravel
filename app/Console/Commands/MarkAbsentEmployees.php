<?php

namespace App\Console\Commands;

use App\Enums\ApprovalStatus;
use App\Enums\PointRuleEnum;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\PointHandlerService;
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
    protected $signature = 'attendance:mark-absent'; /**< Nama dan signature command di terminal */

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert absent attendance records for employees with no attendance after work end time'; /**< Deskripsi singkat fungsi command */

    /**
     * Menjalankan logika command untuk menandai karyawan yang tidak hadir (absent).
     *
     * @param  WorkdayService  $workdayService  Layanan untuk mengecek hari kerja dan libur.
     * @return int Status keluar (0 untuk sukses, 1 untuk gagal)
     */
    public function handle(WorkdayService $workdayService, PointHandlerService $pointHandler): int
    {
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

        $employees = Employee::query()
            ->whereHas('user', function ($q) {
                $q->withoutRole([
                    \App\Enums\UserRole::OWNER->value,
                    \App\Enums\UserRole::DIRECTOR->value,
                ]);
            })
            ->where(function ($query) use ($today) {
                // Case 1: No attendance record at all
                $query->whereDoesntHave('attendances', function ($q) use ($today) {
                    $q->whereDate('date', $today);
                })
                // Case 2: Has attendance but forgot to clock out (illegal/missing clock out)
                    ->orWhereHas('attendances', function ($q) use ($today) {
                        $q->whereDate('date', $today)->whereNull('clock_out');
                    });
            })
            ->whereDoesntHave('leaves', function ($q) use ($today) {
                $q->where('approval_status', ApprovalStatus::APPROVED->value)
                    ->whereDate('date_start', '<=', $today)
                    ->whereDate('date_end', '>=', $today);
            })
            ->get();

        foreach ($employees as $employee) {
            Attendance::updateOrCreate([
                'employee_id' => $employee->id,
                'date' => $today,
            ], [
                'status' => 'absent',
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }

        $pointHandler->trigger(
            $employee->id,
            PointRuleEnum::ABSENT->value,
            'System generated absent record due to no attendance after work hours.'
        );

        $this->info("Inserted {$employees->count()} absent records (Skipped Owner & Director).");

        return self::SUCCESS;
    }
}
