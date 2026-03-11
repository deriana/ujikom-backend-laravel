<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAttendanceCorrectionRequest;
use App\Http\Requests\UpdateAttendanceCorrectionRequest;
use App\Http\Resources\AttendanceCorrectionResource;
use App\Models\AttendanceCorrection;
use App\Services\AttendanceCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AttendanceCorrectionController
 *
 * Controller untuk mengelola pengajuan koreksi absensi oleh karyawan,
 * termasuk proses pengajuan, pembaruan, pembatalan, dan persetujuan (approval).
 */
class AttendanceCorrectionController extends Controller
{
    protected AttendanceCorrectionService $correctionService; /**< Instance dari AttendanceCorrectionService untuk logika bisnis koreksi absensi */

    /**
     * Membuat instance AttendanceCorrectionController baru.
     *
     * @param AttendanceCorrectionService $correctionService
     */
    public function __construct(AttendanceCorrectionService $correctionService)
    {
        $this->correctionService = $correctionService;
    }

    /**
     * Menampilkan daftar pengajuan koreksi absensi berdasarkan role pengguna.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $corrections = $this->correctionService->index(Auth::user());

        return $this->successResponse(
            AttendanceCorrectionResource::collection($corrections),
            'Attendance corrections fetched successfully'
        );
    }

    /**
     * Menampilkan daftar pengajuan koreksi absensi yang memerlukan persetujuan (approval).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexApproval(): JsonResponse
    {
        $approvals = $this->correctionService->indexApproval(Auth::user());

        return $this->successResponse(
            AttendanceCorrectionResource::collection($approvals),
            'Attendance correction approvals fetched successfully'
        );
    }

    /**
     * Menampilkan detail data pengajuan koreksi absensi tertentu.
     *
     * @param AttendanceCorrection $attendance_correction
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(AttendanceCorrection $attendance_correction): JsonResponse
    {
        $this->authorize('view', $attendance_correction);

        $detail = $this->correctionService->show($attendance_correction);

        return $this->successResponse(
            new AttendanceCorrectionResource($detail),
            'Attendance correction details fetched successfully'
        );
    }

    /**
     * Menyimpan pengajuan koreksi absensi baru ke database.
     *
     * @param CreateAttendanceCorrectionRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateAttendanceCorrectionRequest $request): JsonResponse
    {
        $correction = $this->correctionService->store(
            Auth::user(),
            $request->all()
        );

        return $this->successResponse(
            new AttendanceCorrectionResource($correction),
            'Attendance correction submitted successfully',
            201
        );
    }

    /**
     * Memperbarui data pengajuan koreksi absensi yang sudah ada.
     *
     * @param UpdateAttendanceCorrectionRequest $request
     * @param AttendanceCorrection $attendance_correction
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateAttendanceCorrectionRequest $request, AttendanceCorrection $attendance_correction): JsonResponse
    {
        $this->authorize('update', $attendance_correction);

        $updated = $this->correctionService->update($attendance_correction, $request->validated(), Auth::user());

        return $this->successResponse(
            new AttendanceCorrectionResource($updated),
            'Attendance correction updated successfully'
        );
    }

    /**
     * Menghapus atau membatalkan pengajuan koreksi absensi.
     *
     * @param AttendanceCorrection $attendance_correction
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(AttendanceCorrection $attendance_correction): JsonResponse
    {
        $this->authorize('delete', $attendance_correction);

        $this->correctionService->delete($attendance_correction, Auth::user());

        return $this->successResponse(
            null,
            'Attendance correction deleted successfully'
        );
    }

    /**
     * Menangani proses persetujuan (approve) atau penolakan (reject) pengajuan koreksi absensi.
     *
     * @param Request $request
     * @param AttendanceCorrection $attendance_correction
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function approve(Request $request, AttendanceCorrection $attendance_correction): JsonResponse
    {
        $validated = $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        $this->authorize('approve', $attendance_correction);

        $approve = $request->input('approve');
        $note = $request->input('note');

        $updated = $this->correctionService->approve($attendance_correction, Auth::user(), $approve, $note);

        return $this->successResponse(
            new AttendanceCorrectionResource($updated),
            $approve ? 'Attendance correction approved successfully' : 'Attendance correction rejected successfully'
        );
    }
}
