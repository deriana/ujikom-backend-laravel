<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmployeeWorkScheduleRequest;
use App\Http\Requests\UpdateEmployeeWorkScheduleRequest;
use App\Http\Resources\EmployeeWorkScheduleResource;
use App\Models\EmployeeWorkSchedule;
use App\Services\EmployeeWorkScheduleService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EmployeeWorkScheduleController
 *
 * Controller untuk mengelola penugasan jadwal kerja (Work Schedule) kepada karyawan,
 * mencakup operasi CRUD untuk menentukan jadwal kerja rutin bagi setiap karyawan.
 */
class EmployeeWorkScheduleController extends Controller
{
    protected EmployeeWorkScheduleService $employeeWorkScheduleService; /**< Instance dari EmployeeWorkScheduleService untuk logika bisnis jadwal kerja karyawan */

    /**
     * Membuat instance EmployeeWorkScheduleController baru.
     *
     * @param EmployeeWorkScheduleService $employeeWorkScheduleService
     */
    public function __construct(EmployeeWorkScheduleService $employeeWorkScheduleService)
    {
        $this->employeeWorkScheduleService = $employeeWorkScheduleService;
    }

    /**
     * Menampilkan daftar semua penugasan jadwal kerja karyawan.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmployeeWorkSchedule::class);

        $employee_work_schedules = $this->employeeWorkScheduleService->index();

        return $this->successResponse(
            EmployeeWorkScheduleResource::collection($employee_work_schedules),
            'EmployeeWorkSchedules fetched successfully'
        );
    }

    /**
     * Menyimpan penugasan jadwal kerja baru untuk karyawan ke database.
     *
     * @param CreateEmployeeWorkScheduleRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateEmployeeWorkScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeWorkSchedule::class);

        $employee_work_schedule = $this->employeeWorkScheduleService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employee_work_schedule),
            'EmployeeWorkSchedule created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data penugasan jadwal kerja tertentu.
     *
     * @param EmployeeWorkSchedule $employee_work_schedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('view', $employee_work_schedule);

        $employee_work_schedule->load('creator');

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employee_work_schedule),
            'EmployeeWorkSchedule fetched successfully'
        );
    }

    /**
     * Memperbarui data penugasan jadwal kerja karyawan yang sudah ada.
     *
     * @param UpdateEmployeeWorkScheduleRequest $request
     * @param EmployeeWorkSchedule $employee_work_schedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateEmployeeWorkScheduleRequest $request, EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('edit', $employee_work_schedule);

        $updated = $this->employeeWorkScheduleService->update($employee_work_schedule, $request->validated(), Auth::id());

        return $this->successResponse(
            new EmployeeWorkScheduleResource($updated),
            'EmployeeWorkSchedule updated successfully'
        );
    }

    /**
     * Menghapus data penugasan jadwal kerja karyawan dari database.
     *
     * @param EmployeeWorkSchedule $employee_work_schedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('destroy', $employee_work_schedule);

        $deleted = $this->employeeWorkScheduleService->delete($employee_work_schedule);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete EmployeeWorkSchedule', 500);
        }

        return $this->successResponse(null, 'EmployeeWorkSchedule deleted successfully');
    }
}
