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

        try {
            $divisions = $this->divisionService->index();

            return $this->successResponse(
                DivisionResource::collection($divisions),
                'Divisions fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(CreateDivisionRequest $request): JsonResponse
    {
        $this->authorize('create', Division::class);

        try {
            $division = $this->divisionService->store(
                $request->validated(),
                Auth::id()
            );

            return $this->successResponse(
                new DivisionResource($division->load('teams')),
                'Division created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show(Division $division): JsonResponse
    {
        $this->authorize('view', Division::class);

        try {
            return $this->successResponse(
                new DivisionResource($division->load('teams')),
                'Division fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(UpdateDivisionRequest $request, Division $division): JsonResponse
    {
        $this->authorize('edit', Division::class);

        try {
            $updated = $this->divisionService->update($division, $request->validated(), Auth::id());

            return $this->successResponse(
                new DivisionResource($updated->load('teams')),
                'Division updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(Division $division): JsonResponse
    {
        $this->authorize('destroy', Division::class);

        try {
            $this->divisionService->delete($division);

            return $this->successResponse(
                null,
                'Division deleted successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Division::class);

        try {
            $division = $this->divisionService->restore($uuid);

            return $this->successResponse(
                new DivisionResource($division->load('teams')),
                'Division restored successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Division::class);

        try {
            $this->divisionService->forceDelete($uuid);

            return $this->successResponse(
                null,
                'Division permanently deleted'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
