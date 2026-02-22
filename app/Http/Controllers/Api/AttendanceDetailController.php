<?php

namespace App\Http\Controllers\Api;

use App\Exports\AttendancesExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceDetailService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
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
        // $this->authorize('view', Attendance::class);

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

    public function export(Request $request)
    {
        $this->authorize('export', Attendance::class);

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);


        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now()->endOfDay();

        if ($start->diffInDays($end) > 31) {
            throw ValidationException::withMessages([
                'end_date' => 'Maximum export range is 31 days.',
            ]);
        }

        $fileName = 'attendances_' . $start->format('Y-m-d') . '_to_' . $end->format('Y-m-d') . '.xlsx';
        Log::info($fileName);

        return Excel::download(
            new AttendancesExport($validated),
            $fileName
        );
    }
}
