<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Resources\EarlyLeaveDetailResource;
use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EarlyLeaveService
{
    public function index($user)
    {
        $query = EarlyLeave::with([
            'employee.user',
            'attendance',
            'employee.manager', // Tambahkan ini untuk mempermudah pengecekan di Resource
        ]);

        // 1️⃣ OWNER, DIRECTOR, HR, & FINANCE → Melihat SEUA data perusahaan
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // Biarkan tanpa filter agar mereka bisa monitor seluruh karyawan
        }

        // 2️⃣ MANAGER → Melihat miliknya sendiri + milik bawahan yang dia manageri
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ EMPLOYEE → Hanya melihat miliknya sendiri
        else {
            $query->where('employee_id', $user->employee->id);
        }

        // Return menggunakan Resource agar format JSON konsisten
        return $query->latest()->get();
    }

    public function indexApproval($user)
    {
        if (! $user->employee) {
            return collect(); // kosong jika tidak punya employee
        }

        $employeeId = $user->employee->id;

        $query = EarlyLeave::with([
            'employee.user',
            'attendance',
            'approver.user',
        ])
            ->pending() // hanya status pending
            ->whereNull('approved_by_id'); // belum diapprove

        // Manager → hanya bisa approve bawahan, bukan manager/HR
        if ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('employee.user', function ($q) {
                $q->whereDoesntHave('roles', function ($q2) {
                    $q2->whereIn('name', ['manager', 'hr']); // pakai Spatie roles
                });
            })->whereHas('employee', function ($q) use ($employeeId) {
                $q->where('manager_id', $employeeId);
            });
        }
        // Director / HR / Finance / Owner → bisa approve semua
        elseif ($user->hasAnyRole([
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
        ])) {
            // tidak ada filter tambahan
        }
        // Employee biasa → tidak bisa approve
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    public function show(EarlyLeave $earlyLeave)
    {
        $earlyLeave->load([
            'attendance',
            'employee.user',
            'approver.user',
        ]);

        return new EarlyLeaveDetailResource($earlyLeave);
    }

    public function store(array $data): EarlyLeave
    {
        return DB::transaction(function () use ($data) {

            $employee = Employee::findOrFail($data['employee_id']);

            $today = Carbon::today();

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->firstOrFail();

            $this->validateEarlyLeaveEligibility($attendance);

            $attachmentPath = null;

            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $attachmentPath = $data['attachment']
                    ->storeAs('private/early_leave_attachments', $filename);
            }

            return EarlyLeave::create([
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'status' => ApprovalStatus::PENDING->value,
            ]);
        });
    }

    public function update(EarlyLeave $earlyLeave, array $data, $user): EarlyLeave
    {
        return DB::transaction(function () use ($earlyLeave, $data, $user) {

            if (
                $earlyLeave->status !== ApprovalStatus::PENDING->value && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])
            ) {
                throw new Exception('Processed early leave requests cannot be modified.');
            }

            if ($earlyLeave->attendance?->date->isPast() &&
                ! $earlyLeave->attendance->date->isToday()) {
                throw new Exception('Early leave requests can only be modified on the same day.');
            }

            $attachmentPath = $earlyLeave->attachment;

            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {

                if ($earlyLeave->attachment && Storage::exists($earlyLeave->attachment)) {
                    Storage::delete($earlyLeave->attachment);
                }

                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();

                $earlyLeave->attachment = $data['attachment']
                    ->storeAs('private/early_leave_attachments', $filename);
            }

            $earlyLeave->update([
                'reason' => $data['reason'] ?? $earlyLeave->reason,
                'attachment' => $attachmentPath,
            ]);

            return $earlyLeave;
        });
    }

    public function approve(EarlyLeave $earlyLeave, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($earlyLeave, $user, $approve, $note) {

            // 1️⃣ Pastikan masih pending
            if ($earlyLeave->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Early leave request has already been processed.');
            }

            // 2️⃣ Hanya manager langsung atau HR/Admin yang boleh approve
            $isManager = $earlyLeave->employee?->manager_id === optional($user->employee)->id;

            if (
                ! $isManager &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR])
            ) {
                throw new Exception('You do not have permission to process this early leave request.');
            }

            // 3️⃣ Update status
            $earlyLeave->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approved_by_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            return $earlyLeave;
        });
    }

    public function delete(EarlyLeave $earlyLeave, $user): bool
    {
        return DB::transaction(function () use ($earlyLeave) {

            // 3️⃣ Hapus attachment jika ada
            if ($earlyLeave->attachment) {
                Storage::delete($earlyLeave->attachment);
            }

            $earlyLeave->delete();

            return true;
        });
    }

    private function validateEarlyLeaveEligibility(Attendance $attendance): void
    {
        if (! $attendance->clock_in) {
            throw new Exception('You have not clocked in yet.');
        }

        if ($attendance->clock_out) {
            throw new Exception('Early leave cannot be requested after clocking out.');
        }

        if ($attendance->early_leave_minutes <= 0) {
            throw new Exception('No early leave detected for this date.');
        }

        if (EarlyLeave::where('attendance_id', $attendance->id)->exists()) {
            throw new Exception('An early leave request has already been submitted for this attendance.');
        }
    }
}
