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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EarlyLeaveService
{
    public function index($user)
    {
        $query = EarlyLeave::with([
            'employee.user',
            'manager.user',
        ]);

        // 1️⃣ EMPLOYEE → hanya milik sendiri
        if ($user->hasRole(UserRole::EMPLOYEE)) {
            $query->where('employee_id', $user->employee->id);
        }

        // 2️⃣ MANAGER → milik sendiri + bawahan langsung
        elseif ($user->hasRole(UserRole::MANAGER)) {
            $query->where(function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                    ->orWhereHas('employee', function ($sq) use ($user) {
                        $sq->where('manager_id', $user->employee->id);
                    });
            });
        }

        // 3️⃣ HR → hanya milik manager
        elseif ($user->hasRole(UserRole::HR)) {
            $query->whereHas('employee', function ($q) {
                $q->whereNotNull('manager_id');
            });
        }

        return $query->latest()->get();
    }

    public function show(EarlyLeave $earlyLeave)
    {
        $earlyLeave->load([
            'attendance',
            'employee.user',
            'manager.user',
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
                throw new Exception('Early leave yang sudah diproses tidak dapat diubah.');
            }

            if ($earlyLeave->attendance?->date->isPast() &&
                ! $earlyLeave->attendance->date->isToday()) {
                throw new Exception('Early leave hanya bisa diubah di hari yang sama.');
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
                throw new Exception('Early leave sudah diproses.');
            }

            // 2️⃣ Hanya manager langsung atau HR/Admin yang boleh approve
            $isManager = $earlyLeave->employee?->manager_id === optional($user->employee)->id;

            if (
                ! $isManager &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])
            ) {
                throw new Exception('Anda tidak memiliki izin untuk memproses early leave ini.');
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
        return DB::transaction(function () use ($earlyLeave, $user) {

            // 1️⃣ Hanya boleh hapus jika masih pending
            if ($earlyLeave->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Hanya early leave pending yang bisa dihapus.');
            }

            // 2️⃣ Hanya owner atau HR/Admin
            $userEmployeeId = optional($user->employee)->id;

            if (
                ! $user->hasAnyRole([UserRole::ADMIN, UserRole::HR]) &&
                $earlyLeave->employee_id !== $userEmployeeId
            ) {
                throw new Exception('Anda tidak memiliki izin untuk menghapus data ini.');
            }

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
            throw new Exception('Belum melakukan clock in.');
        }

        if ($attendance->clock_out) {
            throw new Exception('Early leave tidak dapat diajukan setelah clock out.');
        }

        if ($attendance->early_leave_minutes <= 0) {
            throw new Exception('Tidak ada early leave pada tanggal tersebut.');
        }

        if (EarlyLeave::where('attendance_id', $attendance->id)->exists()) {
            throw new Exception('Early leave sudah diajukan untuk attendance ini.');
        }
    }
}
