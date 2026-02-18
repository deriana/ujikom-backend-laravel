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

class ShiftTemplateController extends Controller
{
    protected ShiftTemplateService $shiftTemplateService;

    public function __construct(ShiftTemplateService $shiftTemplateService)
    {
        $this->shiftTemplateService = $shiftTemplateService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ShiftTemplate::class);

        $shift_templates = $this->shiftTemplateService->index();

        return $this->successResponse(
            ShiftTemplateResource::collection($shift_templates),
            'ShiftTemplates fetched successfully'
        );
    }

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

    public function show(ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('view', $shift_template);

        $shift_template->load('allowances', 'creator');

        return $this->successResponse(
            new ShiftTemplateResource($shift_template),
            'ShiftTemplate fetched successfully'
        );
    }

    public function update(UpdateShiftTemplateRequest $request, ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('edit', $shift_template);

        $updated = $this->shiftTemplateService->update($shift_template, $request->validated(), Auth::id());

        return $this->successResponse(
            new ShiftTemplateResource($updated),
            'ShiftTemplate updated successfully'
        );
    }

    public function destroy(ShiftTemplate $shift_template): JsonResponse
    {
        $this->authorize('destroy', $shift_template);

        $deleted = $this->shiftTemplateService->delete($shift_template);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete ShiftTemplate', 500);
        }

        return $this->successResponse(null, 'ShiftTemplate deleted successfully');
    }

       public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', ShiftTemplate::class);

        $shiftTemplate = $this->shiftTemplateService->restore($uuid);

        return $this->successResponse(
            new ShiftTemplateResource($shiftTemplate),
            'ShiftTemplate restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', ShiftTemplate::class);

        $this->shiftTemplateService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'ShiftTemplate permanently deleted'
        );
    }

    public function getTrashed()
    {
        $this->authorize('restore', ShiftTemplate::class);

        $shiftTemplates = $this->shiftTemplateService->getTrashed();

        return $this->successResponse(
            ShiftTemplateResource::collection($shiftTemplates),
            'Trashed Allowances fetched successfully'
        );
    }
}
