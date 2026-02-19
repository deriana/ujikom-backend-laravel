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
        DB::beginTransaction();
        try {
            // 1. Validate Face
            $descriptor = is_array($data['descriptor'] ?? null)
                ? $data['descriptor']
                : json_decode($data['descriptor'] ?? '[]', true);

            $faceResult = $this->faceValidator->validate($descriptor);
            $employee = $faceResult['employee'];
            $score = $faceResult['score'];

            $user = $employee->user;

            if ($user->hasRole([\App\Enums\UserRole::DIRECTOR->value, \App\Enums\UserRole::OWNER->value])) {
                throw new AttendanceException('This position is exempt from daily attendance', [
                    'reason' => 'role_exempted',
                ]);
            }

            // 2. Validate Geo Location (if applicable)
            $this->geoValidator->validate(
                (float) ($data['latitude'] ?? 0),
                (float) ($data['longitude'] ?? 0)
            );

            // 3. Validate Workday
            $today = Carbon::today();
            $this->timeValidator->validateWorkday($today);

            // 4. Get or Create Attendance Record
            $attendance = Attendance::firstOrCreate(
                ['employee_id' => $employee->id, 'date' => $today],
                ['status' => 'present']
            );

            $now = Carbon::now();
            $photoPath = isset($data['photo'])
                ? $this->uploader->upload($data['photo'], $employee->id, $today)
                : null;

            $resultMessage = 'Already attended today'; // Default if both filled

            // 5. Process Clock-In or Clock-Out
            $actionType = null;
            if (! $attendance->clock_in) {
                $actionType = 'clock_in';
                $status = $this->processClockIn($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-in successful';
            } elseif (! $attendance->clock_out) {
                $actionType = 'clock_out';
                $status = $this->processClockOut($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-out successful';
            } else {
                throw new AttendanceException('Already clocked in and out today', ['reason' => 'already_clocked_in_out']);
            }

            // 6. Log Success
            $this->logger->logSuccess($actionType, [
                'employee_id' => $employee->id,
                'employee_nik' => $employee->nik,
                'similarity_score' => $score,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $userAgent,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => $resultMessage,
                'data' => $attendance,
            ];

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
        $summary = [
            'success_count' => 0,
            'failed_count' => 0,
            'details' => [],
        ];

        foreach ($data['attendances'] as $item) {
            // Merge global lat/long if not present in item
            $singleData = array_merge($item, [
                'latitude' => $data['latitude'] ?? ($item['latitude'] ?? null),
                'longitude' => $data['longitude'] ?? ($item['longitude'] ?? null),
            ]);

            $res = $this->handleAttendance($singleData, $userAgent);

            if ($res['success']) {
                $summary['success_count']++;
                $summary['details'][] = ['status' => 'Success', 'message' => $res['message']];
            } else {
                $summary['failed_count']++;
                $summary['details'][] = ['status' => 'Failed', 'message' => $res['message']];
            }
        }

        return ['success' => true, 'summary' => $summary];
    }
}
