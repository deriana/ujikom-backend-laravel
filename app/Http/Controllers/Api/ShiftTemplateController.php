<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateShiftTemplateRequest;
use App\Http\Requests\UpdateShiftTemplateRequest;
use App\Http\Resources\ShiftTemplateResource;
use App\Models\ShiftTemplate;
use App\Services\ShiftTemplateService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * Class ShiftTemplateController
 *
 * Controller untuk mengelola template shift kerja (Shift Template),
 * mencakup operasi CRUD, pemulihan data terhapus, dan manajemen jam kerja shift.
 */
class ShiftTemplateController extends Controller
{
    protected ShiftTemplateService $shiftTemplateService; /**< Instance dari ShiftTemplateService untuk logika bisnis template shift */

    /**
     * Membuat instance ShiftTemplateController baru.
     *
     * @param ShiftTemplateService $shiftTemplateService
     */
    public function __construct(ShiftTemplateService $shiftTemplateService)
    {
        $this->shiftTemplateService = $shiftTemplateService;
    }

    /**
     * Menampilkan daftar semua template shift yang tersedia.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', ShiftTemplate::class);

        $shift_templates = $this->shiftTemplateService->index();

        return $this->successResponse(
            ShiftTemplateResource::collection($shift_templates),
            'ShiftTemplates fetched successfully'
        );
    }

    /**
     * Menyimpan template shift baru ke database.
     *
     * @param CreateShiftTemplateRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateShiftTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftTemplate::class);

        $shift_template = $this->shiftTemplateService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new ShiftTemplateResource($shift_template),
            'ShiftTemplate created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data template shift tertentu.
     *
     * @param ShiftTemplate $shift_template
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('view', $shift_template);

        $shift_template->load('allowances', 'creator');

        return $this->successResponse(
            new ShiftTemplateResource($shift_template),
            'ShiftTemplate fetched successfully'
        );
    }

    /**
     * Memperbarui data template shift yang sudah ada.
     *
     * @param UpdateShiftTemplateRequest $request
     * @param ShiftTemplate $shift_template
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateShiftTemplateRequest $request, ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('edit', $shift_template);

        $updated = $this->shiftTemplateService->update($shift_template, $request->validated(), Auth::id());

        return $this->successResponse(
            new ShiftTemplateResource($updated),
            'ShiftTemplate updated successfully'
        );
    }

    /**
     * Menghapus data template shift (Soft Delete).
     *
     * @param ShiftTemplate $shift_template
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('destroy', $shift_template);

        $deleted = $this->shiftTemplateService->delete($shift_template);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete ShiftTemplate', 500);
        }

        return $this->successResponse(null, 'ShiftTemplate deleted successfully');
    }

    /**
     * Memulihkan data template shift yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', ShiftTemplate::class);

        $shiftTemplate = $this->shiftTemplateService->restore($uuid);

        return $this->successResponse(
            new ShiftTemplateResource($shiftTemplate),
            'ShiftTemplate restored successfully'
        );
    }

    /**
     * Menghapus data template shift secara permanen dari database.
     *
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', ShiftTemplate::class);

        $this->shiftTemplateService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'ShiftTemplate permanently deleted'
        );
    }

    /**
     * Mengambil daftar template shift yang berada di dalam trash (terhapus sementara).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTrashed()
    {
        $this->authorize('restore', ShiftTemplate::class);

        $shiftTemplates = $this->shiftTemplateService->getTrashed();

        return $this->successResponse(
            ShiftTemplateResource::collection($shiftTemplates),
            'Trashed ShiftTemplates fetched successfully'
        );
    }
}
