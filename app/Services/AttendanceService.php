<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Exceptions\Attendance\AttendanceException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\EarlyLeave;
use App\Services\Attendance\Internal\AttendanceLogger;
use App\Services\Attendance\Internal\AttendanceUploader;
use App\Services\Attendance\Validators\FaceValidator;
use App\Services\Attendance\Validators\GeoFenceValidator;
use App\Services\Attendance\Validators\TimeValidator;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class AttendanceService
 *
 * Menangani logika utama kehadiran karyawan, termasuk verifikasi wajah,
 * validasi lokasi (geo-fencing), validasi waktu, dan integrasi dengan lembur.
 */
class AttendanceService
{
    /**
     * Membuat instance layanan kehadiran baru dengan dependensi yang diperlukan.
     *
     * @param  FaceValidator  $faceValidator  Validator pengenalan wajah.
     * @param  GeoFenceValidator  $geoValidator  Validator radius lokasi kantor.
     * @param  TimeValidator  $timeValidator  Validator jendela waktu kerja.
     * @param  AttendanceUploader  $uploader  Pengunggah foto kehadiran.
     * @param  AttendanceLogger  $logger  Pencatat log aktivitas kehadiran.
     * @param  OvertimeService  $overtimeService  Layanan manajemen lembur.
     */
    public function __construct(
        protected FaceValidator $faceValidator, /**< Validator untuk verifikasi biometrik wajah */
        protected GeoFenceValidator $geoValidator, /**< Validator untuk pengecekan lokasi geografis */
        protected TimeValidator $timeValidator, /**< Validator untuk pengecekan jadwal dan waktu */
        protected AttendanceUploader $uploader, /**< Layanan untuk mengunggah foto bukti kehadiran */
        protected AttendanceLogger $logger, /**< Layanan untuk mencatat log aktivitas */
        protected OvertimeService $overtimeService, /**< Layanan untuk sinkronisasi data lembur */
        protected PointHandlerService $pointHandler /**< Layanan untuk menangani pemberian poin kehadiran */
    ) {}

    /**
     * Menangani permintaan kehadiran tunggal untuk pengguna yang terautentikasi.
     *
     * @param  array  $data  Data input (descriptor wajah, koordinat, foto).
     * @param  string  $userAgent  Informasi browser/perangkat pengguna.
     * @return array Status keberhasilan dan pesan respon.
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
     * Memproses logika clock-in (masuk) untuk catatan kehadiran.
     *
     * @param  Attendance  $attendance  Objek model kehadiran.
     * @param  Carbon  $now  Waktu saat ini.
     * @param  array  $data  Data tambahan (koordinat).
     * @param  string|null  $photoPath  Path foto yang diunggah.
     */
    protected function processClockIn(Attendance $attendance, Carbon $now, array $data, ?string $photoPath): void
    {
        // 1. Validate the clock-in time window and calculate late minutes
        $timeValidation = $this->timeValidator->validateClockInWindow($attendance->employee, $now);
        $lateMinutes = (int) ($timeValidation['late_minutes'] ?? 0);

        $attendance->update([
            'clock_in' => $now,
            'late_minutes' => $lateMinutes, // TimeValidator returns 0 if within tolerance
            'latitude_in' => $data['latitude'] ?? null,
            'longitude_in' => $data['longitude'] ?? null,
            'clock_in_photo' => $photoPath,
        ]);

        // --- LOGIC POWER-UP START ---
        $finalLateMinutes = $lateMinutes;
        $pointNote = $lateMinutes > 0 ? "Late {$lateMinutes} minutes" : 'On time';

        if ($lateMinutes > 0) {
            // Tentukan kartu mana yang harus dicari berdasarkan durasi telat
            $targetPowerUp = $lateMinutes <= 30
                ? \App\Enums\PowerUpTypeEnum::ANTI_LATE_LIGHT
                : \App\Enums\PowerUpTypeEnum::ANTI_LATE_HARD;

            // Cek apakah punya kartunya di inventory
            $inventory = \App\Models\EmployeeInventories::where('employee_id', $attendance->employee_id)
                ->where('is_used', false)
                ->whereHas('pointItem', function ($q) use ($targetPowerUp) {
                    $q->where('power_up_type', $targetPowerUp);
                })
                ->first();

            if ($inventory) {
                // PAKAI KARTUNYA!
                $inventory->update([
                    'is_used' => true,
                ]);

                // Override nilai telat jadi 0 supaya tidak kena sanksi poin
                $finalLateMinutes = 0;
                $pointNote = "Late {$lateMinutes} minutes (Protected by Power-Up: " . $inventory->pointItem->name . ")";
            }
        }
        // --- LOGIC POWER-UP END ---

        // 2. Trigger point system based on punctuality (with power-up override)
        $this->pointHandler->trigger(
            $attendance->employee_id,
            \App\Enums\PointCategoryEnum::ATTENDANCE,
            $finalLateMinutes,
            $pointNote
        );

        // 3. Prepare the notification message based on punctuality
        $roundedLate = round($lateMinutes);
        $lateMsg = $roundedLate > 0 ? " (Late by {$roundedLate} minutes)" : ' (On time)';
        $powerUpMsg = $finalLateMinutes === 0 && $lateMinutes > 0 ? " (Power-up Applied! Point Safe ✅)" : "";

        // 4. Send a custom notification to the employee
        $attendance->notifyCustom(
            title: 'Clock-In Successful',
            message: "Hello {$attendance->employee->user->name}, you have successfully clocked in at {$now->format('H:i')}{$lateMsg}.{$powerUpMsg}",
            customUsers: collect([$attendance->employee->user])
        );
    }

    /**
     * Memproses logika clock-out (pulang) untuk catatan kehadiran.
     *
     * @param  Attendance  $attendance  Objek model kehadiran.
     * @param  Carbon  $now  Waktu saat ini.
     * @param  array  $data  Data tambahan (koordinat).
     * @param  string|null  $photoPath  Path foto yang diunggah.
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

    if ($timeValidation['early_leave_minutes'] > 0 && !$timeValidation['is_early_leave_approved']) {
            $this->pointHandler->trigger(
                $attendance->employee_id,
                \App\Enums\PointCategoryEnum::ATTENDANCE,
                (int) $timeValidation['early_leave_minutes'],
                "Early leave {$timeValidation['early_leave_minutes']} minutes without approval."
            );
        }

        // 2. TRIGGER POIN: LEMBUR
        if ($timeValidation['overtime_minutes'] >= 60) {
            $this->pointHandler->trigger(
                $attendance->employee_id,
                \App\Enums\PointCategoryEnum::ATTENDANCE,
                (int) $timeValidation['overtime_minutes'],
                "Overtime for {$timeValidation['overtime_minutes']} minutes."
            );
        }

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
     * Menangani permintaan kehadiran massal (misalnya dari terminal bersama).
     *
     * @param  array  $data  Array berisi daftar data kehadiran.
     * @param  string  $userAgent  Informasi perangkat.
     * @return array Ringkasan jumlah berhasil dan gagal beserta detailnya.
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
     * Menjalankan validasi aturan umum kehadiran sebelum pemrosesan data.
     *
     * @param  mixed  $employee  Objek karyawan yang teridentifikasi.
     * @param  Carbon  $date  Tanggal kehadiran.
     * @param  float|null  $latitude  Koordinat lintang.
     * @param  float|null  $longitude  Koordinat bujur.
     * @return array Hasil pemrosesan.
     */
    protected function validateCommonRules($employee, $date, $latitude, $longitude)
    {
        $user = $employee->user;

        // 1. Cek Role (Director/Owner tidak perlu absen)
        if ($user->hasRole([\App\Enums\UserRole::DIRECTOR->value, \App\Enums\UserRole::OWNER->value])) {
            throw new AttendanceException('This position is exempt from daily attendance.');
        }

        // 2. Validasi Hari Kerja & Jadwal
        $this->timeValidator->validateWorkday($date, $employee);
        $scheduleTimes = $this->timeValidator->getEmployeeScheduleTimes($employee, $date);

        // 3. Validasi Geolocation
        $this->geoValidator->validate(
            (float) ($latitude ?? 0),
            (float) ($longitude ?? 0),
            (bool) $scheduleTimes['requires_office_location']
        );
    }

    /**
     * Menjalankan logika inti pemrosesan kehadiran di dalam transaksi database.
     *
     * @param  mixed  $employee  Objek karyawan yang teridentifikasi.
     * @param  float  $score  Skor kemiripan wajah.
     * @param  array  $data  Data input kehadiran.
     * @param  string  $userAgent  Informasi perangkat.
     * @return array Hasil pemrosesan.
     */
    protected function executeProcess($employee, $score, array $data, string $userAgent): array
    {
        DB::beginTransaction();
        try {
            $today = Carbon::today();

            // Validation
            $this->validateCommonRules($employee, $today, $data['latitude'] ?? 0, $data['longitude'] ?? 0);

            // Retrieve or create the attendance record for today
            $attendance = Attendance::firstOrCreate(['employee_id' => $employee->id, 'date' => $today], ['status' => 'present']);
            $now = Carbon::now();
            $photoPath = isset($data['photo']) ? $this->uploader->upload($data['photo'], $employee->id, $today) : null;

            $resultMessage = 'Already completed attendance for today';

            $actionType = null;
            // Determine whether to process Clock-In or Clock-Out
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

            // Log the successful action and commit the transaction
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
            $this->logger->logFailure('System Error: '.$e->getMessage(), ['user_agent' => $userAgent]);

            return [
                'success' => false,
                'message' => 'A system error occurred',
            ];
        }
    }

    /**
     * Memproses permintaan kehadiran manual ketika verifikasi biometrik gagal.
     * Ini akan membuat permintaan koreksi yang memerlukan persetujuan administratif.
     *
     * @param  mixed  $employee  Objek karyawan yang teridentifikasi.
     * @param  array  $data  Data input (koordinat, alasan, lampiran).
     * @param  string  $userAgent  Informasi perangkat pengguna.
     * @return array Hasil dari proses manual.
     */
    public function executeProcessManual($employee, array $data, string $userAgent): array
    {
        DB::beginTransaction();
        try {
            $today = Carbon::today();
            $now = Carbon::now();

            // 1. Perform standard attendance rule validation
            // $this->validateCommonRules($employee, $today, $data['latitude'], $data['longitude']);

            // 2. Retrieve or initialize today's attendance record
            $attendance = Attendance::firstOrCreate(
                ['employee_id' => $employee->id, 'date' => $today],
                ['status' => 'present']
            );

            $isClockIn = false;
            $isClockOut = false;

            if (! $attendance->clock_in) {
                $isClockIn = true;
            } elseif (! $attendance->clock_out) {
                $isClockOut = true;
            } else {
                throw new AttendanceException('You have already completed your attendance for today.');
            }

            // 3. Upload supporting evidence (e.g., photo of camera issue)
            $attachmentPath = null;
            if (! empty($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$data['attachment']->getClientOriginalExtension();
                $attachmentPath = $data['attachment']->storeAs('private/attendance_corrections', $filename);
            }

            // 4. Create a correction request
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'clock_in_requested' => $isClockIn ? $now : null,
                'clock_out_requested' => $isClockOut ? $now : null,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'status' => ApprovalStatus::PENDING->value, // Pending
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => ($isClockIn ? 'Clock-in' : 'Clock-out').' manual request submitted.',
                'data' => $correction,
            ];

        } catch (AttendanceException $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => 'A system error occurred.'];
        }
    }

    /**
     * Mengurai deskriptor wajah mentah menjadi array.
     *
     * @param  mixed  $raw  Data deskriptor (array atau string JSON).
     * @return array Deskriptor dalam format array.
     */
    protected function parseDescriptor($raw): array
    {
        return is_array($raw) ? $raw : json_decode($raw ?? '[]', true);
    }

    /**
     * Mendapatkan string status kehadiran karyawan untuk hari ini.
     *
     * @param  mixed  $employee  Objek karyawan.
     * @return string Status (absent, clocked_in, completed).
     */
    public function getTodayAttendanceStatus($employee): string
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', Carbon::today())
            ->first();
        if (! $attendance) {
            return 'not_started';
        } elseif ($attendance->status === 'absent') {
            return 'absent';
        } elseif ($attendance->clock_in && ! $attendance->clock_out) {
            return 'clocked_in';
        } elseif ($attendance->clock_in && $attendance->clock_out) {
            return 'completed';
        }

        return 'not_started';
    }
}
