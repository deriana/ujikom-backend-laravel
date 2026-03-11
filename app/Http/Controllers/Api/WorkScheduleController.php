<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWorkScheduleRequest;
use App\Http\Requests\UpdateWorkScheduleRequest;
use App\Http\Resources\WorkScheduleResource;
use App\Models\WorkSchedule;
use App\Services\WorkScheduleService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WorkScheduleController
 *
 * Controller untuk mengelola data jadwal kerja rutin (Work Schedule) dalam organisasi,
 * mencakup operasi CRUD, pemulihan data terhapus, dan manajemen jam kerja standar.
 */
class WorkScheduleController extends Controller
{
    protected WorkScheduleService $workScheduleService; /**< Instance dari WorkScheduleService untuk logika bisnis jadwal kerja */

    /**
     * Membuat instance WorkScheduleController baru.
     *
     * @param WorkScheduleService $workScheduleService
     */
    public function __construct(WorkScheduleService $workScheduleService)
    {
        $this->workScheduleService = $workScheduleService;
    }

    /**
     * Menampilkan daftar semua jadwal kerja yang tersedia.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', WorkSchedule::class);

        $workSchedules = $this->workScheduleService->index();

        return $this->successResponse(
            WorkScheduleResource::collection($workSchedules),
            'WorkSchedules fetched successfully'
        );
    }

    /**
     * Menyimpan data jadwal kerja baru ke database.
     *
     * @param CreateWorkScheduleRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateWorkScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', WorkSchedule::class);

        $workSchedule = $this->workScheduleService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'WorkSchedule created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data jadwal kerja tertentu beserta relasi tunjangannya.
     *
     * @param WorkSchedule $workSchedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('view', $workSchedule);

        $workSchedule->load('allowances', 'creator');

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'WorkSchedule fetched successfully'
        );
    }

    /**
     * Memperbarui data jadwal kerja yang sudah ada.
     *
     * @param UpdateWorkScheduleRequest $request
     * @param WorkSchedule $workSchedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('edit', $workSchedule);

        $updated = $this->workScheduleService->update($workSchedule, $request->validated(), Auth::id());

        return $this->successResponse(
            new WorkScheduleResource($updated),
            'WorkSchedule updated successfully'
        );
    }

    /**
     * Menghapus data jadwal kerja (Soft Delete).
     *
     * @param WorkSchedule $workSchedule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('destroy', $workSchedule);

        $this->workScheduleService->delete($workSchedule);

        return $this->successResponse(
            null,
            'WorkSchedule deleted successfully'
        );
    }

    /**
     * Memulihkan data jadwal kerja yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', WorkSchedule::class);

        $workSchedule = $this->workScheduleService->restore($uuid);

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'WorkSchedule restored successfully'
        );
    }

    /**
     * Menghapus data jadwal kerja secara permanen dari database.
     *
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', WorkSchedule::class);

        $this->workScheduleService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'WorkSchedule permanently deleted'
        );
    }

    /**
     * Mengambil daftar jadwal kerja yang berada di dalam trash (terhapus sementara).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTrashed()
    {
        $this->authorize('restore', WorkSchedule::class);

        $allowances = $this->workScheduleService->getTrashed();

        return $this->successResponse(
            WorkScheduleResource::collection($allowances),
            'Trashed WorkSchedules fetched successfully'
        );
    }
}
