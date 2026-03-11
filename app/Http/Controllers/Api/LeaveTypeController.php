<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LeaveTypeController
 *
 * Controller untuk mengelola jenis-jenis cuti (Leave Type) yang tersedia dalam sistem,
 * mencakup operasi CRUD untuk kategori cuti seperti cuti tahunan, sakit, atau alasan lainnya.
 */
class LeaveTypeController extends Controller
{
    protected LeaveTypeService $leaveTypeService; /**< Instance dari LeaveTypeService untuk logika bisnis jenis cuti */

    /**
     * Membuat instance LeaveTypeController baru.
     *
     * @param LeaveTypeService $leaveTypeService
     */
    public function __construct(LeaveTypeService $leaveTypeService)
    {
        $this->leaveTypeService = $leaveTypeService;
    }

    /**
     * Menampilkan daftar semua jenis cuti yang tersedia.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', LeaveType::class);

        $leave_types = $this->leaveTypeService->index();

        return $this->successResponse(
            LeaveTypeResource::collection($leave_types),
            'LeaveTypes fetched successfully'
        );
    }

    /**
     * Menyimpan jenis cuti baru ke database.
     *
     * @param CreateLeaveTypeRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateLeaveTypeRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveType::class);

        $leave_types = $this->leaveTypeService->store(
            $request->validated(),
        );

        return $this->successResponse(
            new LeaveTypeResource($leave_types),
            'LeaveType created successfully',
            201
        );
    }

    /**
     * Memperbarui data jenis cuti yang sudah ada.
     *
     * @param UpdateLeaveTypeRequest $request
     * @param LeaveType $leave_type
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leave_type): JsonResponse
    {
        $this->authorize('edit', $leave_type);

        Log::info($request->validated());

        $updated = $this->leaveTypeService->update($leave_type, $request->validated());

        return $this->successResponse(
            new LeaveTypeResource($updated),
            'LeaveType updated successfully'
        );
    }

    /**
     * Menghapus data jenis cuti dari database.
     *
     * @param LeaveType $leave_type
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(LeaveType $leave_type): JsonResponse
    {
        $this->authorize('destroy', $leave_type);

        $this->leaveTypeService->delete($leave_type);

        return $this->successResponse(
            null,
            'LeaveType deleted successfully'
        );
    }
}
