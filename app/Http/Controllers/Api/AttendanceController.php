<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function bulkAttendance(Request $request)
    {
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.descriptor' => 'required',
            'attendances.*.photo' => 'required|image|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $attendances = collect($request->attendances)->map(function ($item, $index) use ($request) {
            $photo = $request->file("attendances.$index.photo");

            return [
                'descriptor' => $item['descriptor'],
                'photo' => $photo,
            ];
        })->toArray();

        $payload = [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'attendances' => $attendances,
        ];

        Log::info('Bulk payload parsed', ['count' => count($attendances)]);

        $result = $this->attendanceService->handleBulkAttendance(
            $payload,
            $request->header('User-Agent')
        );

        Log::info('BULK_ATTENDANCE_REQUEST', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'faces_count' => count($attendances),
            'faces' => collect($attendances)->map(function ($a, $i) {
                $desc = is_array($a['descriptor'])
                    ? $a['descriptor']
                    : json_decode($a['descriptor'], true);

                return [
                    'index' => $i,
                    'descriptor_length' => is_array($desc) ? count($desc) : null,
                    'descriptor_sample' => is_array($desc) ? array_slice($desc, 0, 5) : null,
                    'photo_present' => ! empty($a['photo']),
                ];
            }),
        ]);

        return $this->successResponse($result['summary'], 'Bulk Attendance Processed');
    }
}
