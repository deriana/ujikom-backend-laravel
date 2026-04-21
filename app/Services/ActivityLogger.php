<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Manually log an activity.
     *
     * @param string $action      e.g., 'Login', 'Logout', 'Export Data'
     * @param string $description Human readable description of the activity
     * @param string $module      The module or feature area (e.g., 'Auth', 'Payroll')
     * @param mixed  $payload     Additional data to store (will be JSON encoded)
     * @return \App\Models\ActivityLog
     */
    public static function log(string $action, string $description, string $module = 'System', $payload = null)
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
