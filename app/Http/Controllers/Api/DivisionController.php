<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDivisionRequest;
use App\Http\Requests\UpdateDivisionRequest;
use App\Http\Resources\DivisionResource;
use App\Models\Division;
use App\Services\DivisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DivisionController extends Controller
{
    protected DivisionService $divisionService;

    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService = $divisionService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Division::class);

        $divisions = $this->divisionService->index();

        return $this->successResponse(
            DivisionResource::collection($divisions),
            'Divisions fetched successfully'
        );
    }

    public function store(CreateDivisionRequest $request): JsonResponse
    {
        $this->authorize('create', Division::class);

        $division = $this->divisionService->store($request->validated(), Auth::id());

        return $this->successResponse(
            new DivisionResource($division->load('teams')),
            'Division created successfully',
            201
        );
    }

    public function show(Division $division): JsonResponse
    {
        $this->authorize('view', $division);

        return $this->successResponse(
            new DivisionResource($division->load('teams')),
            'Division fetched successfully'
        );
    }

    public function update(UpdateDivisionRequest $request, Division $division): JsonResponse
    {
        $this->authorize('edit', $division);

        $updated = $this->divisionService->update($division, $request->validated(), Auth::id());

        return $this->successResponse(
            new DivisionResource($updated->load('teams')),
            'Division updated successfully'
        );
    }

    public function destroy(Division $division): JsonResponse
    {
        $this->authorize('destroy', $division);

        $this->divisionService->delete($division);

        return $this->successResponse(null, 'Division deleted successfully');
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Division::class);

        $division = $this->divisionService->restore($uuid);

        return $this->successResponse(
            new DivisionResource($division->load('teams')),
            'Division restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Division::class);

        $this->divisionService->forceDelete($uuid);

        return $this->successResponse(null, 'Division permanently deleted');
    }

    public function getTrashed(): JsonResponse
    {
        $this->authorize('viewAny', Division::class);

        $divisions = $this->divisionService->getTrashed();

        return $this->successResponse(
            DivisionResource::collection($divisions),
            'Trashed Divisions fetched successfully'
        );
    }
}
