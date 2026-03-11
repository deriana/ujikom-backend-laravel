<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\AttendanceCorrection;
use App\Services\Attendance\Validators\TimeValidator;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class AttendanceCorrectionService
 *
 * Menangani logika bisnis untuk pengajuan koreksi kehadiran karyawan,
 * termasuk validasi waktu, manajemen status persetujuan, dan pembaruan data kehadiran.
 */
class AttendanceCorrectionService
{
    protected TimeValidator $timeValidator; /**< Validator untuk menghitung jadwal dan keterlambatan */

    /**
     * Membuat instance layanan koreksi kehadiran baru.
     *
     * @param TimeValidator $timeValidator
     */
    public function __construct(TimeValidator $timeValidator)
    {
        $this->timeValidator = $timeValidator;
    }

    /**
     * Mengambil semua data koreksi kehadiran dengan filter berdasarkan peran pengguna.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data koreksi kehadiran.
     */
    public function index($user)
    {
        // 1. Initialize query with necessary relationships
        $query = AttendanceCorrection::with(['employee.user', 'attendance', 'approver.user']);

        // 2. High-level Roles (Admin, Owner, Director, HR, Finance) -> Can see all data
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::OWNER->value,
            UserRole::DIRECTOR->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            // No filter applied
        }
        // 3. Manager -> Can see own requests and direct subordinates
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            $employeeId = $user->employee->id;
            $query->where(function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId)
                    ->orWhereHas('employee', fn ($sq) => $sq->where('manager_id', $employeeId));
            });
        } else {
            // 4. Employee -> Can only see their own requests
            $query->where('employee_id', $user->employee->id);
        }

        return $query->latest()->get();
    }

    /**
     * Mengambil daftar pengajuan koreksi kehadiran yang sedang menunggu persetujuan.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data pengajuan yang tertunda.
     */
    public function indexApproval($user)
    {
        // 1. Initialize base query for pending requests
        $query = AttendanceCorrection::with(['employee.user', 'attendance', 'approver.user'])
            ->pending()
            ->whereNull('approved_at');

        // 2. Peran Tingkat Tinggi -> Akses penuh ke semua pengajuan tertunda
        if ($user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
            // No additional filter
        }

        // 3. Logika Manajer -> Hanya bisa melihat persetujuan untuk bawahan langsung
        elseif ($user->hasRole(UserRole::MANAGER->value)) {
            if (! $user->employee) {
                return collect();
            }

            $employeeId = $user->employee->id;
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $employeeId));
        }

        // 4. Fallback untuk peran lainnya
        else {
            return collect();
        }

        return $query->latest()->get();
    }

    /**
     * Menampilkan detail dari pengajuan koreksi kehadiran tertentu.
     *
     * @return AttendanceCorrection Objek koreksi kehadiran dengan relasi yang dimuat.
     */
    public function show(AttendanceCorrection $correction)
    {
        // 1. Muat relasi untuk tampilan detail
        return $correction->load(['employee.user', 'attendance', 'approver.user', 'employee.team.division', 'employee.position']);
    }

    /**
     * Menyimpan pengajuan koreksi kehadiran baru.
     *
     * @param  \App\Models\User  $user
     * @param array $data Data pengajuan (attendance_id, employee_id, clock_in_requested, dll).
     * @throws Exception
     */
    public function store($user, array $data): AttendanceCorrection
    {
        return DB::transaction(function () use ($data) {
            // 0. Validasi sederhana: Jam pulang tidak boleh sebelum jam masuk
            $clockIn = Carbon::parse($data['clock_in_requested']);
            $clockOut = Carbon::parse($data['clock_out_requested']);
            if ($clockOut->lt($clockIn)) {
                throw new Exception('Waktu jam pulang yang diminta tidak boleh lebih awal dari jam masuk.');
            }

            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/attendance_corrections', $filename);
            }

            // 1. Buat catatan koreksi
            $correction = AttendanceCorrection::create([
                'attendance_id' => $data['attendance_id'],
                'employee_id' => $data['employee_id'],
                'clock_in_requested' => $data['clock_in_requested'],
                'clock_out_requested' => $data['clock_out_requested'],
                'reason' => $data['reason'],
                'attachment' => $attachmentPath ?? null,
                'status' => ApprovalStatus::PENDING->value,
            ]);

            // 2. Kirim notifikasi
            $correction->notifyCustom(
                title: 'New Attendance Correction Request',
                message: 'Your attendance correction request has been submitted.'
            );

            return $correction;
        });
    }

    /**
     * Memperbarui pengajuan koreksi kehadiran yang sudah ada.
     *
     * @param  \App\Models\User  $user
     * @param array $data Data pembaruan.
     * @throws Exception
     */
    public function update(AttendanceCorrection $correction, array $data, $user): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $data, $user) {
            // 1. Validasi apakah pengajuan masih bisa diubah
            if ($correction->status !== ApprovalStatus::PENDING->value &&
                ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN])) {
                throw new Exception('Koreksi yang sudah diproses tidak dapat diubah.');
            }

            // 0. Validasi sederhana: Jam pulang tidak boleh sebelum jam masuk
            $clockIn = Carbon::parse($data['clock_in_requested'] ?? $correction->clock_in_requested);
            $clockOut = Carbon::parse($data['clock_out_requested'] ?? $correction->clock_out_requested);
            if ($clockOut->lt($clockIn)) {
                throw new Exception('Waktu jam pulang yang diminta tidak boleh lebih awal dari jam masuk.');
            }

            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                if ($correction->attachment && Storage::exists($correction->attachment)) {
                    Storage::delete($correction->attachment);
                }
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $correction->attachment = $data['attachment']
                    ->storeAs('private/attendance_corrections', $filename);
            }

            // 2. Perbarui catatan
            $correction->update([
                'clock_in_requested' => $data['clock_in_requested'] ?? $correction->clock_in_requested,
                'clock_out_requested' => $data['clock_out_requested'] ?? $correction->clock_out_requested,
                'reason' => $data['reason'] ?? $correction->reason,
                'attachment' => $correction->attachment,
            ]);

            // 3. Kirim notifikasi
            $correction->notifyCustom(
                title: 'Correction Request Updated',
                message: 'Your attendance correction request has been updated.'
            );

            return $correction;
        });
    }

    /**
     * Memproses persetujuan atau penolakan pengajuan koreksi kehadiran.
     *
     * @param  \App\Models\User  $user
     * @param AttendanceCorrection $correction Objek koreksi.
     * @param bool $approve Status persetujuan (true untuk setuju, false untuk tolak).
     * @param string|null $note Catatan dari penyetuju.
     * @return AttendanceCorrection Objek koreksi yang telah diperbarui.
     * @throws Exception
     */
    public function approve(AttendanceCorrection $correction, $user, bool $approve, ?string $note = null)
    {
        return DB::transaction(function () use ($correction, $user, $approve, $note) {
            // 1. Pastikan pengajuan masih dalam status tertunda
            if ($correction->status !== ApprovalStatus::PENDING->value) {
                throw new Exception('Koreksi sudah diproses sebelumnya.');
            }

            // 2. Cek Izin: Hanya manajer langsung atau peran tingkat tinggi yang bisa memproses
            $isManager = $correction->employee?->manager_id === optional($user->employee)->id;
            if (! $isManager && ! $user->hasAnyRole([UserRole::HR, UserRole::ADMIN, UserRole::DIRECTOR, UserRole::OWNER])) {
                throw new Exception('Anda tidak memiliki izin untuk menyetujui koreksi ini.');
            }

            // 3. Perbarui status pengajuan
            $correction->update([
                'status' => $approve ? ApprovalStatus::APPROVED->value : ApprovalStatus::REJECTED->value,
                'approver_id' => optional($user->employee)->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            // 4. Jika disetujui, perbarui catatan kehadiran yang sebenarnya
            if ($approve) {
                $attendance = $correction->attendance;
                $employee = $correction->employee;

                $schedule = $this->timeValidator->getEmployeeScheduleTimes($employee, Carbon::parse($attendance->date));

                $requestedIn = $correction->clock_in_requested ? Carbon::parse($correction->clock_in_requested) : null;
                $requestedOut = $correction->clock_out_requested ? Carbon::parse($correction->clock_out_requested) : null;

                $lateMinutes = 0;
                if ($requestedIn && $requestedIn->greaterThan($schedule['work_start_time'])) {
                    $diffIn = $requestedIn->diffInMinutes($schedule['work_start_time']);
                    if ($diffIn > $schedule['late_tolerance_minutes']) {
                        $lateMinutes = $diffIn;
                    }
                }

                $earlyLeaveMinutes = 0;
                if ($requestedOut && $requestedOut->lessThan($schedule['work_end_time'])) {
                    $earlyLeaveMinutes = $requestedOut->diffInMinutes($schedule['work_end_time']);
                }

                $workMinutes = 0;
                if ($requestedIn && $requestedOut) {
                    $workMinutes = $requestedIn->diffInMinutes($requestedOut);
                }

                $newStatus = 'present';

                $attendance->update([
                    'clock_in' => $requestedIn,
                    'clock_out' => $requestedOut,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'work_minutes' => $workMinutes,
                    'is_corrected' => true,
                    'status' => $newStatus,
                ]);
            }
            // 5. Kirim notifikasi ke karyawan
            $correction->notifyCustom(
                title: $approve ? 'Correction Approved' : 'Correction Rejected',
                message: $approve
                    ? "Your attendance correction has been approved by {$user->name}."
                    : "Your attendance correction has been rejected by {$user->name}."
            );

            return $correction;
        });
    }

    /**
     * Menghapus pengajuan koreksi kehadiran.
     *
     * @param AttendanceCorrection $attendanceCorrection Objek koreksi yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     */
    public function delete(AttendanceCorrection $attendanceCorrection): bool
    {
        return DB::transaction(function () use ($attendanceCorrection) {
            $attendanceCorrection->notifyCustom(
                title: 'AttendanceCorrection Request Deleted',
                message: "Employee {$attendanceCorrection->employee->user->name} has deleted their attendanceCorrection request for {$attendanceCorrection->attendance->date->toFormattedDateString()}."
            );

            return $attendanceCorrection->delete();
        });
    }
}
