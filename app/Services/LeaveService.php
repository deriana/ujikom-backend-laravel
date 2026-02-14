<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Resources\LeaveDetailResource;
use App\Http\Resources\LeaveResource;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveApproval;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveService
{
    /**
     * List leave untuk index/table
     */
    public function index($user)
    {
        $query = Leave::with(['employee', 'leaveType', 'approvals']);

        // filter berdasarkan role
        if ($user->hasRole(UserRole::EMPLOYEE)) {
            $query->where('employee_id', $user->employee->id);
        } elseif ($user->hasRole(UserRole::MANAGER)) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
        }

        return LeaveResource::collection($query->latest()->get());
    }

    public function show(Leave $leave)
    {
        $leave->load(['employee', 'leaveType', 'approvals', 'employeeLeave', 'employee.leaveBalances']);

        return new LeaveDetailResource($leave);
    }

    /**
     * Buat leave baru
     */
    public function store(array $data, $user)
    {
        return DB::transaction(function () use ($data) {

            $employeeId = $data['employee_id'];
            $leaveTypeId = $data['leave_type_id'];
            $isHalfDay = $data['is_half_day'] ?? false;

            $start = Carbon::parse($data['date_start']);
            $end = Carbon::parse($data['date_end']);

            // 🔥 Recalculate duration (jangan percaya request)
            $daysRequested = $this->calculateWorkDays(
                $start->toDateString(),
                $end->toDateString(),
                $isHalfDay
            );

            // 🔒 Lock saldo
            $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new \Exception('Saldo cuti tidak ditemukan');
            }

            if ($balance->remaining_days < $daysRequested) {
                throw new \Exception('Saldo cuti tidak mencukupi');
            }

            // Attachment
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/leave_attachments', $filename);
            }

            $employee = Employee::findOrFail($employeeId);

            $leave = Leave::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'date_start' => $start,
                'date_end' => $end,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'approval_status' => ApprovalStatus::PENDING->value,
                'is_half_day' => $isHalfDay,
                'duration' => $daysRequested,
            ]);

            LeaveApproval::create([
                'leave_id' => $leave->id,
                'approver_id' => $employee->manager_id,
                'level' => 0,
                'status' => ApprovalStatus::PENDING->value,
            ]);

            LeaveApproval::create([
                'leave_id' => $leave->id,
                'approver_id' => null,
                'level' => 1,
                'status' => ApprovalStatus::PENDING->value,
            ]);

            return $leave;
        });
    }

    /**
     * Update leave
     */
    public function update(Leave $leave, array $data, $user)
    {
        return DB::transaction(function () use ($leave, $data, $user) {

            Log::info($leave);

            if (
                $leave->approval_status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR])
            ) {
                throw new Exception('Leave yang sudah diproses tidak dapat diubah.');
            }

            // 2️⃣ Jangan izinkan ubah leave yang sudah lewat
            if (now()->gt($leave->date_start)) {
                throw new Exception('Leave yang sudah berjalan atau lewat tidak dapat diubah.');
            }

            $oldDuration = $leave->duration;

            // 3️⃣ Hitung ulang durasi
            $newDuration = $this->calculateWorkDays(
                $data['date_start'],
                $data['date_end'],
                $data['is_half_day'] ?? false
            );

            // 4️⃣ Jika tanggal berubah → reset approval
            if (
                $leave->date_start != $data['date_start'] ||
                $leave->date_end != $data['date_end']
            ) {
                $leave->approval_status = ApprovalStatus::PENDING->value;
            }

            // 5️⃣ Handle attachment
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {

                if ($leave->attachment && Storage::exists($leave->attachment)) {
                    Storage::delete($leave->attachment);
                }

                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $leave->attachment = $data['attachment']
                    ->storeAs('private/leave_attachments', $filename);
            }

            // 6️⃣ Update leave
            $leave->update([
                'date_start' => $data['date_start'],
                'date_end' => $data['date_end'],
                'reason' => $data['reason'],
                'is_half_day' => $data['is_half_day'] ?? false,
                'duration' => $newDuration,
            ]);

            return $leave;
        });
    }

    /**
     * Approve / Reject leave
     */
    public function approve(LeaveApproval $approval, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($approval, $approve, $note) {
            if ($approval->status !== ApprovalStatus::PENDING->value) {
                throw new \Exception('Approval sudah diproses');
            }

            $approval->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_at' => now(),
                'note' => $note,
            ]);

            $leave = $approval->leave;

            // update approval_status di leave
            if ($leave->approvals()->where('status', ApprovalStatus::PENDING->value)->count() === 0) {
                $allApproved = $leave->approvals()->where('status', ApprovalStatus::REJECTED->value)->count() === 0;
                $leave->approval_status = $allApproved ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value;
                $leave->save();

                // jika full approved → buat EmployeeLeave & update balance
                if ($allApproved) {
                    $days = $this->calculateWorkDays($leave->date_start, $leave->date_end, $leave->is_half_day);

                    EmployeeLeave::create([
                        'employee_id' => $leave->employee_id,
                        'leave_type_id' => $leave->leave_type_id,
                        'start_date' => $leave->date_start,
                        'end_date' => $leave->date_end,
                        'days_taken' => $days,
                        'status' => ApprovalStatus::APPROVED->value,
                    ]);

                    $balance = EmployeeLeaveBalance::firstOrCreate(
                        ['employee_id' => $leave->employee_id, 'leave_type_id' => $leave->leave_type_id, 'year' => now()->year],
                        ['total_days' => $leave->leaveType->default_days, 'used_days' => 0]
                    );

                    $balance->useDays($days);
                }
            }

            return $approval;
        });
    }

    /**
     * Delete / cancel leave
     */
    public function delete(Leave $leave, $user): bool
    {
        return DB::transaction(function () use ($leave, $user) {

            // 🔒 Pastikan hanya leave pending
            if ((string) $leave->approval_status !== (string) ApprovalStatus::PENDING->value) {
                throw new Exception('Hanya leave pending yang bisa dihapus.');
            }

            // 🔒 Cek hak akses: admin/HR atau owner leave
            $userEmployeeId = optional($user->employee)->id;
            if (
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) &&
                $leave->employee_id !== $userEmployeeId
            ) {
                throw new Exception('Anda tidak memiliki izin untuk menghapus leave ini.');
            }

            // 🔹 Hapus semua approval terkait
            $leave->approvals()->delete();

            // 🔹 Hapus leave
            $leave->delete();

            return true;
        });
    }

    private function calculateWorkDays(string $start, string $end, bool $isHalfDay = false): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($startDate->gt($endDate)) {
            return 0;
        }

        $year = $startDate->year;

        // Ambil holiday yang overlap range
        $holidays = Holiday::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        })->get();

        // Expand semua holiday menjadi array tanggal
        $holidayDates = [];

        foreach ($holidays as $holiday) {

            if ($holiday->is_recurring) {
                // Recurring: pakai bulan & tanggal, ganti tahun sesuai request
                $startRecurring = Carbon::create(
                    $year,
                    Carbon::parse($holiday->start_date)->month,
                    Carbon::parse($holiday->start_date)->day
                );

                $endRecurring = Carbon::create(
                    $year,
                    Carbon::parse($holiday->end_date)->month,
                    Carbon::parse($holiday->end_date)->day
                );

                $period = CarbonPeriod::create($startRecurring, $endRecurring);
            } else {
                $period = CarbonPeriod::create(
                    Carbon::parse($holiday->start_date),
                    Carbon::parse($holiday->end_date)
                );
            }

            foreach ($period as $date) {
                $holidayDates[$date->toDateString()] = true;
            }
        }

        // Hitung hari kerja
        $days = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {

            if ($date->isWeekend()) {
                continue;
            }

            if (isset($holidayDates[$date->toDateString()])) {
                continue;
            }

            $days++;
        }

        return (float) $days;
    }
}
