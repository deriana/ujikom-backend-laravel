<?php

namespace App\Services\Attendance\Internal;

use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Log;

class AttendanceLogger
{
    public function log(array $context): void
    {
        // Add request info if missing
        $defaults = [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $data = array_merge($defaults, $context);

        AttendanceLog::create([
            'employee_id' => $data['employee_id'] ?? null,
            'employee_nik' => $data['employee_nik'] ?? null,
            'status' => $data['status'],
            'action' => $data['action'] ?? null, // clock_in, clock_out
            'reason' => $data['reason'] ?? null,
            'similarity_score' => $data['similarity_score'] ?? null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        // Log::info('ATTENDANCE_EVENT', $data);
    }

    public function logFailure(string $reason, array $context = []): void
    {
        $this->log(array_merge($context, [
            'status' => 'failed',
            'reason' => $reason,
        ]));
    }

    public function logSuccess(string $action, array $context = []): void
    {
        $this->log(array_merge($context, [
            'status' => 'success',
            'action' => $action,
        ]));
    }
}
