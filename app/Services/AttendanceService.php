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
     * Handle a single attendance request for the authenticated user.
     */
    public function handleAttendance(array $data, string $userAgent): array
    {
        try {
            // 1. Retrieve the currently authenticated user and verify employee profile
            $user = Auth::user();
            if (! $user->employee) {
                return ['success' => false, 'message' => 'Employee profile not found.'];
            }

            // 2. Parse the face descriptor from the request data
            $descriptor = $this->parseDescriptor($data['descriptor'] ?? null);

            // 3. Perform 1:1 face verification against the employee's registered biometrics
            $faceResult = $this->faceValidator->verifyMatch($user->employee, $descriptor);

            // 4. Execute the core attendance processing logic
            return $this->executeProcess($faceResult['employee'], $faceResult['score'], $data, $userAgent);

        } catch (AttendanceException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'A system error occurred.'];
        }
    }

    /**
     * Process the clock-in logic for an attendance record.
     */
    protected function processClockIn(Attendance $attendance, Carbon $now, array $data, ?string $photoPath): void
    {
        // 1. Validate the clock-in time window and calculate late minutes
        $timeValidation = $this->timeValidator->validateClockInWindow($attendance->employee, $now);

        $attendance->update([
            'clock_in' => $now,
            'late_minutes' => $timeValidation['late_minutes'], // TimeValidator returns 0 if within tolerance
            'latitude_in' => $data['latitude'] ?? null,
            'longitude_in' => $data['longitude'] ?? null,
            'clock_in_photo' => $photoPath,
        ]);

        // 2. Prepare the notification message based on punctuality
        $lateMsg = $timeValidation['late_minutes'] > 0
            ? " (Late by {$timeValidation['late_minutes']} minutes)"
            : ' (On time)';

        // 3. Send a custom notification to the employee
        $attendance->notifyCustom(
            title: 'Clock-In Successful',
            message: "Hello {$attendance->employee->user->name}, you have successfully clocked in at {$now->format('H:i')}{$lateMsg}.",
            customUsers: collect([$attendance->employee->user])
        );
    }

    /**
     * Process the clock-out logic for an attendance record.
     */
    protected function processClockOut(Attendance $attendance, Carbon $now, array $data, ?string $photoPath): void
    {
        // 1. Validate the clock-out window and calculate early leave or overtime
        $timeValidation = $this->timeValidator->validateClockOutWindow($attendance->employee, $now);
        $workMinutes = $attendance->clock_in->diffInMinutes($now);

        // 2. Update the attendance record with clock-out details
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

        // 3. Update related early leave request if it exists and is approved
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

        // 4. Send a custom notification to the employee
        $attendance->notifyCustom(
            title: 'Clock-Out Successful',
            message: "Thank you for your hard work today, {$attendance->employee->user->name}! You clocked out at {$now->format('H:i')}. Total work duration: {$hours} hours {$minutes} minutes.",
            customUsers: collect([$attendance->employee->user])
        );

        // 5. Trigger overtime service to update duration if applicable
        $this->overtimeService->updateDurationAfterClockOut($attendance);
    }

    /**
     * Handle bulk attendance requests (e.g., from a shared terminal).
     */
    public function handleBulkAttendance(array $data, string $userAgent): array
    {
        $summary = ['success_count' => 0, 'failed_count' => 0, 'details' => []];

        // 1. Iterate through each attendance item in the bulk request
        foreach ($data['attendances'] as $item) {
            try {
                // 2. Merge global coordinates with individual item data
                $singleData = array_merge($item, [
                    'latitude' => $data['latitude'] ?? ($item['latitude'] ?? null),
                    'longitude' => $data['longitude'] ?? ($item['longitude'] ?? null),
                ]);

                // 3. Parse descriptor and perform 1:N face validation
                $descriptor = $this->parseDescriptor($item['descriptor'] ?? null);
                $faceResult = $this->faceValidator->validate($descriptor);

                // 4. Execute the core process for the identified employee
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

    /**
     * Execute the core attendance processing logic within a transaction.
     */
    protected function executeProcess($employee, $score, array $data, string $userAgent): array
    {
        DB::beginTransaction();
        try {
            $user = $employee->user;

            // 1. Check if the user's role is exempt from daily attendance
            if ($user->hasRole([\App\Enums\UserRole::DIRECTOR->value, \App\Enums\UserRole::OWNER->value])) {
                throw new AttendanceException('This position is exempt from daily attendance.');
            }

            // 2. Validate geographical location and workday status
            $this->geoValidator->validate((float) ($data['latitude'] ?? 0), (float) ($data['longitude'] ?? 0));
            $today = Carbon::today();
            $this->timeValidator->validateWorkday($today);

            // 3. Retrieve or create the attendance record for today
            $attendance = Attendance::firstOrCreate(['employee_id' => $employee->id, 'date' => $today], ['status' => 'present']);
            $now = Carbon::now();
            $photoPath = isset($data['photo']) ? $this->uploader->upload($data['photo'], $employee->id, $today) : null;

            $resultMessage = 'Already completed attendance for today';

            $actionType = null;
            // 4. Determine whether to process Clock-In or Clock-Out
            if (! $attendance->clock_in) {
                $actionType = 'clock_in';
                $this->processClockIn($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-in successful';
            } elseif (! $attendance->clock_out) {
                $actionType = 'clock_out';
                $this->processClockOut($attendance, $now, $data, $photoPath);
                $resultMessage = 'Clock-out successful';
            } else {
                throw new AttendanceException('You have already clocked in and out today.');
            }

            // 5. Log the successful action and commit the transaction
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
            // Log the specific attendance failure
            $this->logger->logFailure($e->getMessage(), array_merge($e->getContext(), [
                'user_agent' => $userAgent,
                'employee_id' => $employee->id ?? null,
            ]));

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            // Log generic system errors
            $this->logger->logFailure('System Error: '.$e->getMessage(), ['user_agent' => $userAgent]);

            return [
                'success' => false,
                'message' => 'A system error occurred',
            ];
        }
    }

    /**
     * Parse the raw face descriptor into an array.
     */
    protected function parseDescriptor($raw): array
    {
        return is_array($raw) ? $raw : json_decode($raw ?? '[]', true);
    }

    /**
     * Get the attendance status string for an employee for the current day.
     */
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
