<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAttendanceSubmissionRequest;
use App\Http\Requests\UpdateAttendanceSubmissionRequest;
use App\Http\Resources\AttendanceRequestDetailResource;
use App\Http\Resources\AttendanceRequestResource;
use App\Models\AttendanceRequest;
use App\Services\AttendanceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class AttendanceRequestController extends Controller
{
    protected AttendanceRequestService $attendanceRequestService;

    public function __construct(AttendanceRequestService $attendanceRequestService)
    {
        $this->attendanceRequestService = $attendanceRequestService;
    }

    /**
     * Index / list leaves
     */
    public function index(): JsonResponse
    {
        $attendanceRequests = $this->attendanceRequestService->index(Auth::user());

        return $this->successResponse(
            AttendanceRequestResource::collection($attendanceRequests),
            'Leaves fetched successfully'
        );
    }

    public function indexApproval(): JsonResponse
    {
        $approvals = $this->attendanceRequestService->indexApproval(Auth::user());

        return $this->successResponse(
            AttendanceRequestResource::collection($approvals),
            'Leave approvals fetched successfully'
        );
    }

    /**
     * Show detail leave
     */
    public function show(AttendanceRequest $attendance_request): JsonResponse
    {
        $this->authorize('view', $attendance_request);

        $detail = $this->attendanceRequestService->show($attendance_request);

        return $this->successResponse(
            new AttendanceRequestDetailResource($detail),
            'AttendanceRequest details fetched successfully'
        );
    }

    /**
     * Create attendanceRequest
     */
    public function store(CreateAttendanceSubmissionRequest $request): JsonResponse
    {
        $attendanceRequest = $this->attendanceRequestService->store(
            $request->all(),
            Auth::user()
        );

        return $this->successResponse(
            new AttendanceRequestResource($attendanceRequest),
            'AttendanceRequest created successfully',
            201
        );
    }

    /**
     * Update attendanceRequest
     */
    public function update(UpdateAttendanceSubmissionRequest $request, AttendanceRequest $attendance_request): JsonResponse
    {
        $this->authorize('update', $attendance_request);

        $updated = $this->attendanceRequestService->update($attendance_request, $request->validated(), Auth::user());

        return $this->successResponse(
            new AttendanceRequestResource($updated),
            'AttendanceRequest updated successfully'
        );
    }

    /**
     * Delete / cancel attendanceRequest (soft delete)
     */
    public function destroy(AttendanceRequest $attendance_request): JsonResponse
    {
        $this->authorize('delete', $attendance_request);

        $this->attendanceRequestService->delete($attendance_request, Auth::user());

        return $this->successResponse(
            null,
            'AttendanceRequest deleted successfully'
        );
    }

    /**
     * Approval
     */
    public function approve(Request $request, AttendanceRequest $attendance_request): JsonResponse
    {
        // 1. Validasi input (Sangat disarankan)
        $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        // 2. Authorize
        $this->authorize('approve', $attendance_request);

        // 3. Ambil nilai dari request
        $approve = $request->input('approve');
        $note = $request->input('note');

        // 4. Panggil service
        $updated = $this->attendanceRequestService->approve($attendance_request, Auth::user(), $approve, $note);

        return $this->successResponse(
            $updated,
            $approve ? 'Leave approved successfully' : 'Leave rejected successfully'
        );
    }
}
