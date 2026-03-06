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

class WorkScheduleController extends Controller
{
    protected WorkScheduleService $workScheduleService;

    public function __construct(WorkScheduleService $workScheduleService)
    {
        $this->workScheduleService = $workScheduleService;
    }

    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', WorkSchedule::class);

        $workSchedules = $this->workScheduleService->index();

        return $this->successResponse(
            WorkScheduleResource::collection($workSchedules),
            'WorkSchedules fetched successfully'
        );
    }

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

    public function show(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('view', $workSchedule);

        $workSchedule->load('allowances', 'creator');

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'WorkSchedule fetched successfully'
        );
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('edit', $workSchedule);

        $updated = $this->workScheduleService->update($workSchedule, $request->validated(), Auth::id());

        return $this->successResponse(
            new WorkScheduleResource($updated),
            'WorkSchedule updated successfully'
        );
    }

    public function destroy(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('destroy', $workSchedule);

        $this->workScheduleService->delete($workSchedule);

        return $this->successResponse(
            null,
            'WorkSchedule deleted successfully'
        );
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', WorkSchedule::class);

        $workSchedule = $this->workScheduleService->restore($uuid);

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'WorkSchedule restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', WorkSchedule::class);

        $this->workScheduleService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'WorkSchedule permanently deleted'
        );
    }

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
