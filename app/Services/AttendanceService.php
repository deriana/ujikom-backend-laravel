<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Exceptions\Attendance\AttendanceException;
use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Services\Attendance\Internal\AttendanceLogger;
use App\Services\Attendance\Internal\AttendanceUploader;
use App\Services\Attendance\Validators\FaceValidator;
use App\Services\Attendance\Validators\GeoFenceValidator;
use App\Services\Attendance\Validators\TimeValidator;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function __construct(
        protected FaceValidator $faceValidator,
        protected GeoFenceValidator $geoValidator,
        protected TimeValidator $timeValidator,
        protected AttendanceUploader $uploader,
        protected AttendanceLogger $logger,
        protected OvertimeService $overtimeService

    ) {}

    /**
     * Handle single attendance request
     */
    public function handleAttendance(array $data, string $userAgent): array
    {
        try {
            $user = Auth::user();
            if (!$user->employee) {
                return ['success' => false, 'message' => 'Profil karyawan tidak ditemukan.'];
            }

            $descriptor = $this->parseDescriptor($data['descriptor'] ?? null);

            // Verifikasi wajah (1:1 matching dengan data user login)
            $faceResult = $this->faceValidator->verifyMatch($user->employee, $descriptor);

            // Jalankan core logic
            return $this->executeProcess($faceResult['employee'], $faceResult['score'], $data, $userAgent);

        } catch (AttendanceException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            Log::error('Attendance Error: '.$e->getMessage());

            return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
        }
    }

    protected function processClockIn(Attendance $attendance, Carbon $now, array $data, ?string $photoPath): void
    {
        $timeValidation = $this->timeValidator->validateClockInWindow($attendance->employee, $now);

        $attendance->update([
            'clock_in' => $now,
            'late_minutes' => $timeValidation['late_minutes'], // TimeValidator returns 0 if within tolerance
            'latitude_in' => $data['latitude'] ?? null,
            'longitude_in' => $data['longitude'] ?? null,
            'clock_in_photo' => $photoPath,
        ]);

        $lateMsg = $timeValidation['late_minutes'] > 0
            ? " (Late by {$timeValidation['late_minutes']} minutes)"
            : ' (On time)';

        $attendance->notifyCustom(
            title: 'Clock-In Successful',
            message: "Hello {$attendance->employee->user->name}, you have successfully clocked in at {$now->format('H:i')}{$lateMsg}.",
            customUsers: collect([$attendance->employee->user])
        );
    }

    protected function processClockOut(Attendance $attendance, Carbon $now, array $data, ?string $photoPath): void
    {
        $timeValidation = $this->timeValidator->validateClockOutWindow($attendance->employee, $now);
        $workMinutes = $attendance->clock_in->diffInMinutes($now);

        $attendance->update([
            'clock_out' => $now,
            'early_leave_minutes' => $timeValidation['early_leave_minutes'],
            'is_early_leave_approved' => $timeValidation['is_early_leave_approved'],
            'overtime_minutes' => $timeValidation['overtime_minutes'],
            'work_minutes' => $attendance->clock_in->diffInMinutes($now),
            'latitude_out' => $data['latitude'] ?? null,
            'longitude_out' => $data['longitude'] ?? null,
            'clock_out_photo' => $photoPath,
        ]);

        $earlyLeave = EarlyLeave::where('attendance_id', $attendance->id)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->first();

        if ($earlyLeave && $timeValidation['early_leave_minutes'] > 0) {
            $earlyLeave->update([
                'minutes_early' => $timeValidation['early_leave_minutes'],
            ]);
        }

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;

        $attendance->notifyCustom(
            title: 'Clock-Out Successful',
            message: "Thank you for your hard work today, {$attendance->employee->user->name}! You clocked out at {$now->format('H:i')}. Total work duration: {$hours} hours {$minutes} minutes.",
            customUsers: collect([$attendance->employee->user])
        );

        $this->overtimeService->updateDurationAfterClockOut($attendance);

    }

    /**
     * Handle bulk attendance
     */
    public function handleBulkAttendance(array $data, string $userAgent): array
    {
        $summary = ['success_count' => 0, 'failed_count' => 0, 'details' => []];

        foreach ($data['attendances'] as $item) {
            try {
                $singleData = array_merge($item, [
                    'latitude' => $data['latitude'] ?? ($item['latitude'] ?? null),
                    'longitude' => $data['longitude'] ?? ($item['longitude'] ?? null),
                ]);

                $descriptor = $this->parseDescriptor($item['descriptor'] ?? null);

                $faceResult = $this->faceValidator->validate($descriptor);

                $res = $this->executeProcess($faceResult['employee'], $faceResult['score'], $singleData, $userAgent);

                $summary['success_count']++;
                $summary['details'][] = ['status' => 'Success', 'message' => $res['message']];
            } catch (Exception $e) {
                $summary['failed_count']++;
                $summary['details'][] = ['status' => 'Failed', 'message' => $e->getMessage()];
            }
        }

        return ['success' => true, 'summary' => $summary];
    }

    protected function executeProcess($employee, $score, array $data, string $userAgent): array
    {
        DB::beginTransaction();
        try {
            $user = $employee->user;

            // 1. Cek Role Terlarang
            if ($user->hasRole([\App\Enums\UserRole::DIRECTOR->value, \App\Enums\UserRole::OWNER->value])) {
                throw new AttendanceException('Jabatan ini dibebaskan dari absensi harian.');
            }

            // 2. Validasi Lokasi & Hari Kerja
            $this->geoValidator->validate((float) ($data['latitude'] ?? 0), (float) ($data['longitude'] ?? 0));
            $today = Carbon::today();
            $this->timeValidator->validateWorkday($today);

            // 3. Record Absensi
            $attendance = Attendance::firstOrCreate(['employee_id' => $employee->id, 'date' => $today], ['status' => 'present']);
            $now = Carbon::now();
            $photoPath = isset($data['photo']) ? $this->uploader->upload($data['photo'], $employee->id, $today) : null;

            $resultMessage = 'Already attended today'; // Default if both filled

            // 4. Proses Clock In/Out
            $actionType = null;
            if (! $attendance->clock_in) {
                $actionType = 'clock_in';
                $this->processClockIn($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-in berhasil';
            } elseif (! $attendance->clock_out) {
                $actionType = 'clock_out';
                $this->processClockOut($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-out berhasil';
            } else {
                throw new AttendanceException('Sudah melakukan clock-in dan clock-out hari ini.');
            }

            // 5. Log & Commit
            $this->logger->logSuccess($actionType, [
                'employee_id' => $employee->id,
                'employee_nik' => $employee->nik,
                'similarity_score' => $score,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $userAgent,
            ]);

            DB::commit();

            return ['success' => true, 'message' => $resultMessage, 'data' => $attendance];

        } catch (AttendanceException $e) {
            DB::rollBack();
            // Log failure explicitly
            $this->logger->logFailure($e->getMessage(), array_merge($e->getContext(), [
                'user_agent' => $userAgent,
                'employee_id' => $employee->id ?? null, // Attempt to get ID if available
            ]));

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $this->logger->logFailure('System Error: '.$e->getMessage(), ['user_agent' => $userAgent]);

            Log::error('System Error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'A system error occurred',
            ];
        }
    }

    protected function parseDescriptor($raw): array
    {
        return is_array($raw) ? $raw : json_decode($raw ?? '[]', true);
    }

    public function getTodayAttendanceStatus($employee): string
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', Carbon::today())
            ->first();
        if (! $attendance) {
            return 'absent';
        } elseif ($attendance->clock_in && ! $attendance->clock_out) {
            return 'clocked_in';
        } elseif ($attendance->clock_in && $attendance->clock_out) {
            return 'completed';
        } else {
            return 'absent';
        }
    }
}
