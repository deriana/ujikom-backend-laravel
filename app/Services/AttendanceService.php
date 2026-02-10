<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\BiometricUser;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    /**
     * Match descriptor dari FE dengan semua employee
     * Mengembalikan employee_id jika match, null jika tidak
     */
    protected function matchDescriptor(array $inputDescriptor): array
    {
        $biometrics = BiometricUser::with('employee')->get();

        $bestScore = -1;
        $matchedEmployee = null;

        foreach ($biometrics as $bio) {
            $desc = $bio->descriptor;
            if (! is_array($desc)) {
                continue;
            }

            $score = $this->cosineSimilarity($inputDescriptor, $desc);

            if ($score > $bestScore) {
                $bestScore = $score;
                $matchedEmployee = $bio->employee;
            }
        }

        Log::info($bestScore);

        return [
            'employee' => ($bestScore > 0.75) ? $matchedEmployee : null,
            'score' => $bestScore, // ✅
        ];
    }

    /**
     * Cosine similarity untuk descriptor
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }

        $dot = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Handle 1 attendance
     */
    public function handleAttendance(array $data, string $userAgent)
    {
        if (! isset($data['descriptor'])) {
            $this->logAttendance([
                'status' => 'failed',
                'reason' => 'descriptor_missing',
                'user_agent' => $userAgent,
            ]);

            return ['success' => false, 'message' => 'Descriptor tidak ditemukan'];
        }

        $inputDescriptor = is_array($data['descriptor'])
            ? $data['descriptor']
            : json_decode($data['descriptor'], true);

        if (! is_array($inputDescriptor)) {
            $this->logAttendance([
                'status' => 'failed',
                'reason' => 'descriptor_invalid_format',
                'user_agent' => $userAgent,
            ]);

            return ['success' => false, 'message' => 'Format descriptor tidak valid'];
        }

        $match = $this->matchDescriptor($inputDescriptor);
        $employee = $match['employee'];
        $score = $match['score'];

        if (! $employee) {
            $this->logAttendance([
                'status' => 'failed',
                'reason' => 'face_not_recognized',
                'similarity_score' => $score,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $userAgent,
            ]);

            return ['success' => false, 'message' => 'Wajah tidak dikenali'];
        }

        $result = $this->recordAttendance($employee, $data, $score, $userAgent);

        return $result;
    }

    /**
     * Handle bulk attendance
     */
    public function handleBulkAttendance(array $data, string $userAgent)
    {
        $summary = [
            'success_count' => 0,
            'failed_count' => 0,
            'details' => [],
        ];

        foreach ($data['attendances'] as $index => $item) {
            $singleData = array_merge($item, [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $res = $this->handleAttendance($singleData, $userAgent);

            if ($res['success']) {
                $summary['success_count']++;
                $summary['details'][] = [
                    'status' => 'Success',
                    'message' => $res['message'],
                ];
            } else {
                $summary['failed_count']++;
                $summary['details'][] = [
                    'status' => 'Failed',
                    'message' => $res['message'],
                ];
            }
        }

        return ['success' => true, 'summary' => $summary];
    }

    /**
     * Simpan record attendance + foto
     */
    protected function recordAttendance($employee, array $data, float $score, string $userAgent)
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $setting = $this->getAttendanceSetting();

        $workStart = Carbon::createFromFormat('H:i', $setting['work_start_time']);
        $workEnd = Carbon::createFromFormat('H:i', $setting['work_end_time']);
        $lateTolerance = (int) $setting['late_tolerance_minutes'];

        $maxClockIn = (clone $workStart)->addHours(2);
        $minClockOut = (clone $workStart)->addHours($workStart->diffInHours($workEnd) / 2);

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            ['status' => 'present']
        );

        // ================= CLOCK IN =================
        if (! $attendance->clock_in) {

            if ($now->gt($maxClockIn)) {
                $this->logAttendance([
                    'status' => 'failed',
                    'employee_id' => $employee->id,
                    'employee_nik' => $employee->nik,
                    'reason' => 'clock_in_over_limit',
                    'similarity_score' => $score,
                    'user_agent' => $userAgent,
                ]);

                return ['success' => false, 'message' => 'Melewati batas waktu absen masuk'];
            }

            $lateMinutes = max(0, $workStart->diffInMinutes($now, false));
            if ($lateMinutes <= $lateTolerance) {
                $lateMinutes = 0;
            }

            $photoInPath = isset($data['photo'])
            ? $this->storeAttendancePhoto($data['photo'], $employee->id, $today)
            : null;

            $attendance->update([
                'clock_in' => $now,
                'late_minutes' => $lateMinutes,
                'latitude_in' => $data['latitude'] ?? null,
                'longitude_in' => $data['longitude'] ?? null,
                'clock_in_photo' => $photoInPath,
            ]);

            $this->logAttendance([
                'status' => 'success',
                'action' => 'clock_in',
                'employee_id' => $employee->id,
                'employee_nik' => $employee->nik,
                'similarity_score' => $score,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $userAgent,
            ]);

            return ['success' => true, 'message' => 'Clock-in berhasil', 'data' => $attendance];
        }

        // ================= CLOCK OUT =================
        if (! $attendance->clock_out) {

            if ($now->lt($minClockOut)) {
                $this->logAttendance([
                    'status' => 'failed',
                    'employee_id' => $employee->id,
                    'employee_nik' => $employee->nik,
                    'reason' => 'clock_out_too_early',
                    'similarity_score' => $score,
                    'user_agent' => $userAgent,
                ]);

                return ['success' => false, 'message' => 'Belum boleh absen pulang'];
            }

            $earlyLeaveMinutes = $now->lt($workEnd) ? $now->diffInMinutes($workEnd) : 0;
            $overtimeMinutes = $now->gt($workEnd) ? $workEnd->diffInMinutes($now) : 0;

            $photoOutPath = isset($data['photo'])
            ? $this->storeAttendancePhoto($data['photo'], $employee->id, $today)
            : null;

            $attendance->update([
                'clock_out' => $now,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'work_minutes' => $attendance->clock_in->diffInMinutes($now),
                'latitude_out' => $data['latitude'] ?? null,
                'longitude_out' => $data['longitude'] ?? null,
                'clock_out_photo' => $photoOutPath,
            ]);

            $this->logAttendance([
                'status' => 'success',
                'action' => 'clock_out',
                'employee_id' => $employee->id,
                'employee_nik' => $employee->nik,
                'similarity_score' => $score,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $userAgent,
            ]);

            return ['success' => true, 'message' => 'Clock-out berhasil', 'data' => $attendance];
        }

        $this->logAttendance([
            'status' => 'failed',
            'employee_id' => $employee->id,
            'employee_nik' => $employee->nik,
            'reason' => 'already_clocked_in_out',
            'similarity_score' => $score,
            'user_agent' => $userAgent,
        ]);

        return ['success' => false, 'message' => 'Sudah absen hari ini'];
    }

    protected function logAttendance(array $context): void
    {
        AttendanceLog::create([
            'employee_id' => $context['employee_id'] ?? null,
            'employee_nik' => $context['employee_nik'] ?? null,
            'status' => $context['status'],
            'action' => $context['action'] ?? null,
            'reason' => $context['reason'] ?? null,
            'similarity_score' => $context['similarity_score'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => $context['user_agent'] ?? null,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
        ]);

        Log::info('ATTENDANCE_EVENT', $context);
    }

    protected function getAttendanceSetting(): array
    {
        $setting = Setting::where('key', 'attendance')->first();

        return $setting?->values ?? [
            'late_tolerance_minutes' => 10,
            'work_start_time' => '09:00',
            'work_end_time' => '17:00',
        ];
    }

    protected function storeAttendancePhoto($photo, $employeeId, Carbon $date): ?string
    {
        $folderPath = 'attendance/'.$date->format('Y/m/d');
        $fileName = 'photo_'.$employeeId.'_'.now()->timestamp.'_'.uniqid();

        // ================= FILE UPLOAD (DARI FORM DATA) =================
        if ($photo instanceof UploadedFile) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $path = $photo->storeAs($folderPath, $fileName.'.'.$ext, 'public');

            return $path;
        }

        // ================= BASE64 (LEGACY SUPPORT) =================
        if (is_string($photo) && str_contains($photo, 'base64')) {
            $base64 = explode(',', $photo)[1] ?? null;
            if (! $base64) {
                return null;
            }

            $decoded = base64_decode($base64);

            $finfo = finfo_open();
            $mime = finfo_buffer($finfo, $decoded, FILEINFO_MIME_TYPE);

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => null
            };

            if (! $ext) {
                Log::warning('Invalid attendance photo mime type', ['mime' => $mime]);

                return null;
            }

            Storage::disk('public')->put($folderPath.'/'.$fileName.'.'.$ext, $decoded);

            return $folderPath.'/'.$fileName.'.'.$ext;
        }

        return null;
    }
}
