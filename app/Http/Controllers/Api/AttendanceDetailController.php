<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceDetailService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AttendanceDetailController extends Controller
{
    protected $attendanceDetailService;

    public function __construct(AttendanceDetailService $attendanceDetailService)
    {
        $this->attendanceDetailService = $attendanceDetailService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->only(['start_date', 'end_date']);

        $attendances = $this->attendanceDetailService->index($filters);

        return $this->successResponse(
            AttendanceResource::collection($attendances),
            'Attendance fetched successfully',
            200
        );
    }

    public function show(Attendance $attendance): JsonResponse
    {
        $this->authorize('view', Attendance::class);

        $attendance = $this->attendanceDetailService->show($attendance);

        if (! $attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Attendance fetched successfully',
            200
        );
    }
}
