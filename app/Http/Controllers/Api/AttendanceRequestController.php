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

/**
 * Class AttendanceRequestController
 *
 * Controller untuk mengelola pengajuan kehadiran (Attendance Request) seperti tugas luar,
 * dinas, atau koreksi manual oleh karyawan dan proses persetujuannya.
 */
class AttendanceRequestController extends Controller
{
    protected AttendanceRequestService $attendanceRequestService; /**< Instance dari AttendanceRequestService untuk logika bisnis pengajuan kehadiran */

    /**
     * Membuat instance AttendanceRequestController baru.
     *
     * @param AttendanceRequestService $attendanceRequestService
     */
    public function __construct(AttendanceRequestService $attendanceRequestService)
    {
        $this->attendanceRequestService = $attendanceRequestService;
    }

    /**
     * Menampilkan daftar pengajuan kehadiran berdasarkan role pengguna.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $attendanceRequests = $this->attendanceRequestService->index(Auth::user());

        return $this->successResponse(
            AttendanceRequestResource::collection($attendanceRequests),
            'Attendance requests fetched successfully'
        );
    }

    /**
     * Menampilkan daftar pengajuan kehadiran yang memerlukan persetujuan (approval).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexApproval(): JsonResponse
    {
        $approvals = $this->attendanceRequestService->indexApproval(Auth::user());

        return $this->successResponse(
            AttendanceRequestResource::collection($approvals),
            'Attendance request approvals fetched successfully'
        );
    }

    /**
     * Menampilkan detail data pengajuan kehadiran tertentu.
     *
     * @param AttendanceRequest $attendance_request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menyimpan pengajuan kehadiran baru ke database.
     *
     * @param CreateAttendanceSubmissionRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Memperbarui data pengajuan kehadiran yang sudah ada.
     *
     * @param UpdateAttendanceSubmissionRequest $request
     * @param AttendanceRequest $attendance_request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menghapus atau membatalkan pengajuan kehadiran (soft delete).
     *
     * @param AttendanceRequest $attendance_request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menangani proses persetujuan (approve) atau penolakan (reject) pengajuan kehadiran.
     *
     * @param Request $request
     * @param AttendanceRequest $attendance_request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
            $approve ? 'Attendance request approved successfully' : 'Attendance request rejected successfully'
        );
    }
}
