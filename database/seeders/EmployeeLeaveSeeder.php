<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmployeeLeaveSeeder extends Seeder
{
    /**
     * Jalankan seeder EmployeeLeave berdasarkan Leave yang sudah approved
     */
    public function run(): void
    {
        // 1. Ambil semua leave yang PENDING lalu jadikan APPROVED secara acak
        // agar ada data yang masuk ke tabel EmployeeLeave
        $pendingLeaves = Leave::where('approval_status', ApprovalStatus::PENDING->value)->get();
        foreach ($pendingLeaves as $pending) {
            if (rand(0, 1)) { // 50% kemungkinan untuk diapprove
                $pending->update(['approval_status' => ApprovalStatus::APPROVED->value]);
            }
        }

        // 2. Tarik data yang sudah APPROVED
        $leaves = Leave::with(['leaveType', 'employee'])
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->get();

        foreach ($leaves as $leave) {
            // Logika hitung hari
            $days = $leave->is_half_day ? 0.5 : $leave->date_start->diffInDays($leave->date_end) + 1;

            // Buat Record di EmployeeLeave (Data final untuk Payroll)
            EmployeeLeave::updateOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date' => $leave->date_start->toDateString(),
                    'end_date' => $leave->date_end->toDateString(),
                ],
                [
                    'uuid' => Str::uuid(),
                    'days_taken' => $days,
                    'status' => ApprovalStatus::APPROVED->value,
                    'created_by_id' => $leave->employee->user_id, // Asumsi dibuat oleh user terkait
                ]
            );

            // 3. Update saldo EmployeeLeaveBalance
            $balance = EmployeeLeaveBalance::firstOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year' => $leave->date_start->year,
                ],
                [
                    'total_days' => $leave->leaveType->default_days ?? 0,
                    'used_days' => 0,
                ]
            );

            // Panggil method useDays dari model untuk kurangi saldo
            // Pastikan di Model EmployeeLeaveBalance sudah ada method useDays()
            $balance->useDays($days);
        }
    }
}
