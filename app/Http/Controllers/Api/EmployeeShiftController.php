<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmployeeShiftRequest;
use App\Http\Requests\UpdateEmployeeShiftRequest;
use App\Http\Resources\EmployeeShiftResource;
use App\Models\EmployeeShift;
use App\Services\EmployeeShiftService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EmployeeShiftController
 *
 * Controller untuk mengelola penugasan shift kerja kepada karyawan,
 * mencakup operasi CRUD untuk jadwal shift spesifik.
 */
class EmployeeShiftController extends Controller
{
    protected EmployeeShiftService $employeeShiftService; /**< Instance dari EmployeeShiftService untuk logika bisnis jadwal shift karyawan */

    /**
     * Membuat instance EmployeeShiftController baru.
     *
     * @param EmployeeShiftService $employeeShiftService
     */
    public function __construct(EmployeeShiftService $employeeShiftService)
    {
        $this->employeeShiftService = $employeeShiftService;
    }

    /**
     * Menampilkan daftar semua penugasan shift karyawan.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmployeeShift::class);

        $employee_shifts = $this->employeeShiftService->index();

        return $this->successResponse(
            EmployeeShiftResource::collection($employee_shifts),
            'EmployeeShifts fetched successfully'
        );
    }

    /**
     * Menyimpan penugasan shift baru untuk karyawan ke database.
     *
     * @param CreateEmployeeShiftRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateEmployeeShiftRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeShift::class);

        $employee_shift = $this->employeeShiftService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new EmployeeShiftResource($employee_shift),
            'EmployeeShift created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data penugasan shift tertentu.
     *
     * @param EmployeeShift $employee_shift
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('view', $employee_shift);

        $employee_shift->load('employee', 'shiftTemplate', 'creator');

        return $this->successResponse(
            new EmployeeShiftResource($employee_shift),
            'EmployeeShift fetched successfully'
        );
    }

    /**
     * Memperbarui data penugasan shift karyawan yang sudah ada.
     *
     * @param UpdateEmployeeShiftRequest $request
     * @param EmployeeShift $employee_shift
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateEmployeeShiftRequest $request, EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('edit', $employee_shift);

        $updated = $this->employeeShiftService->update($employee_shift, $request->validated(), Auth::id());

        return $this->successResponse(
            new EmployeeShiftResource($updated),
            'EmployeeShift updated successfully'
        );
    }

    /**
     * Menghapus data penugasan shift karyawan dari database.
     *
     * @param EmployeeShift $employee_shift
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('destroy', $employee_shift);

        $deleted = $this->employeeShiftService->delete($employee_shift);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete EmployeeShift', 500);
        }

        return $this->successResponse(null, 'EmployeeShift deleted successfully');
    }
}
