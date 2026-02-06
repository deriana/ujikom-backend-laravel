<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    protected PositionService $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $positions = $this->positionService->index();

        return $this->successResponse(
            PositionResource::collection($positions),
            'Positions fetched successfully'
        );
    }

    public function store(CreatePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = $this->positionService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new PositionResource($position),
            'Position created successfully',
            201
        );
    }

    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        $position->load('allowances', 'creator');

        return $this->successResponse(
            new PositionResource($position),
            'Position fetched successfully'
        );
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('edit', $position);

        $updated = $this->positionService->update($position, $request->validated(), Auth::id());

        return $this->successResponse(
            new PositionResource($updated),
            'Position updated successfully'
        );
    }

    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('destroy', $position);

        $this->positionService->delete($position);

        return $this->successResponse(
            null,
            'Position deleted successfully'
        );
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Position::class);

        $position = $this->positionService->restore($uuid);

        return $this->successResponse(
            new PositionResource($position),
            'Position restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Position::class);

        $this->positionService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'Position permanently deleted'
        );
    }
}
