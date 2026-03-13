<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class AttendanceController
 *
 * Controller untuk menangani proses absensi karyawan menggunakan pengenalan wajah (biometrik),
 * mendukung absensi tunggal, absensi massal (bulk), dan pengecekan status harian.
 */
class AttendanceController extends Controller
{
    protected $attendanceService; /**< Instance dari AttendanceService untuk logika pemrosesan absensi */

    /**
     * Membuat instance AttendanceController baru.
     *
     * @param AttendanceService $attendanceService
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Menangani permintaan absensi untuk satu orang (Single Attendance).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function singleAttendance(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'descriptor' => 'required',
            'photo' => 'required|image|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $payload = [
            'descriptor' => $request->descriptor,
            'photo' => $request->file('photo'),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        // Log::info('Single attendance payload parsed', ['payload' => $payload]);

        $result = $this->attendanceService->handleAttendance(
            $payload,
            $request->header('User-Agent')
        );

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse(
            $result['data'] ?? null,
            $result['message']
        );
    }

    /**
     * Menangani permintaan absensi untuk banyak orang sekaligus (Bulk Attendance).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        // Log::info('Bulk payload parsed', ['count' => count($attendances)]);

        $result = $this->attendanceService->handleBulkAttendance(
            $payload,
            $request->header('User-Agent')
        );

        // Log::info('BULK_ATTENDANCE_REQUEST', [
        //     'ip' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        //     'latitude' => $request->latitude,
        //     'longitude' => $request->longitude,
        //     'faces_count' => count($attendances),
        //     'faces' => collect($attendances)->map(function ($a, $i) {
        //         $desc = is_array($a['descriptor'])
        //             ? $a['descriptor']
        //             : json_decode($a['descriptor'], true);

        //         return [
        //             'index' => $i,
        //             'descriptor_length' => is_array($desc) ? count($desc) : null,
        //             'descriptor_sample' => is_array($desc) ? array_slice($desc, 0, 5) : null,
        //             'photo_present' => ! empty($a['photo']),
        //         ];
        //     }),
        // ]);

        return $this->successResponse($result['summary'], 'Bulk Attendance Processed');
    }

    /**
     * Mengambil status kehadiran karyawan yang sedang login untuk hari ini.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendanceStatusToday(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->employee) {
            return $this->errorResponse('Profil karyawan tidak ditemukan.', 404);
        }

        $status = $this->attendanceService->getTodayAttendanceStatus($user->employee);

        return $this->successResponse(['status' => $status], 'Today attendance status retrieved');
    }
}
