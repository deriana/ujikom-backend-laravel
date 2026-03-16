<?php

namespace App\Console\Commands;

use App\Enums\EmployeeState;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoDeactivateEmployee extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'app:auto-deactivate-employee'; /**< Nama dan signature command di terminal */

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Automatically deactivate user accounts if the contract_end date has passed'; /**< Deskripsi singkat fungsi command */

    /**
     * Menjalankan logika command untuk menonaktifkan akun karyawan yang kontraknya telah berakhir.
     *
     * @return int Status keluar (0 untuk sukses)
     */
    public function handle()
    {
        $today = now()->toDateString();

        // 1. Find Employees whose contracts expired today or earlier
        // AND whose User account is still active
        $expiredEmployees = Employee::whereNotNull('contract_end')
            ->where('contract_end', '<', $today)
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->with('user')
            ->get();

        if ($expiredEmployees->isEmpty()) {
            $this->info("No employees found with expired contracts today.");
            return 0;
        }

        $this->info("Found " . $expiredEmployees->count() . " employees with expired contracts.");

        foreach ($expiredEmployees as $employee) {
            try {
                // 2. Set Custom Notification (Using employee -> user relation)
                $employee->customNotification = [
                    'title'   => 'Contract Automatically Ended',
                    'message' => "System deactivated {$employee->user->name} (NIK: {$employee->nik}) because the contract/probation period ended on {$employee->contract_end->format('d M Y')}.",
                    'url'     => "/users/{$employee->nik}/show",
                ];

                DB::transaction(function () use ($employee) {
                    // 3. Disable login access in the users table via relation
                    $employee->user->update([
                        'is_active' => false
                    ]);

                    // 4. Update resign_date as a termination marker (optional)
                    // If you want contract expiration to be treated as 'resigned'
                    if (is_null($employee->resign_date)) {
                        $employee->update([
                            'employement_state' => EmployeeState::RESIGNED->value,
                            'resign_date' => $employee->contract_end
                        ]);
                    }
                });

                $this->line("Successfully deactivated: {$employee->user->name}");

            } catch (\Exception $e) {
                $this->error("Failed to process {$employee->nik}: " . $e->getMessage());
                Log::error("AutoDeactivate Error for {$employee->nik}: " . $e->getMessage());
            }
        }

        $this->info("Finished processing all employees.");
        return 0;
    }
}
