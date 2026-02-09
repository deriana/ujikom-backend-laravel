<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function bulkAttendance(Request $request)
    {
        $validated = $request->validate([
            'attendances' => 'required|array', // Menampung banyak data
            'attendances.*.descriptor' => 'required',
            'attendances.*.photo' => 'required|image|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Kirim seluruh array ke service
        $result = $this->attendanceService->handleBulkAttendance(
            $validated,
            $request->header('User-Agent')
        );

        return $this->successResponse($result['summary'], 'Bulk Attendance Processed');
    }
}
