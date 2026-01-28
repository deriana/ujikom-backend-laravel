<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAllowanceRequest;
use App\Http\Requests\UpdateAllowanceRequest;
use App\Http\Resources\AllowanceResource;
use App\Http\Resources\DivisionResource;
use App\Models\Allowance;
use App\Services\AllowanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AllowanceController extends Controller
{
    protected AllowanceService $allowanceService;

    public function __construct(AllowanceService $allowanceService)
    {
        $this->allowanceService = $allowanceService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Allowance::class);

        try {
            $allowances = $this->allowanceService->index();

            return $this->successResponse(
                AllowanceResource::collection($allowances),
                'Allowances fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(CreateAllowanceRequest $request): JsonResponse
    {
        $this->authorize('create', Allowance::class);

        try {
            $allowance = $this->allowanceService->store(
                $request->validated(),
                Auth::id()
            );

            return $this->successResponse(
                new AllowanceResource($allowance),
                'Allowance created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show(Allowance $allowance): JsonResponse
    {
        $this->authorize('view', Allowance::class);

        try {
            return $this->successResponse(
                new AllowanceResource($allowance),
                'Allowance fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(UpdateAllowanceRequest $request, Allowance $allowance): JsonResponse
    {
        $this->authorize('edit', Allowance::class);

        try {
            $updated = $this->allowanceService->update($allowance, $request->validated(), Auth::id());

            return $this->successResponse(
                new AllowanceResource($updated),
                'Allowance updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(Allowance $allowance): JsonResponse
    {
        $this->authorize('destroy', Allowance::class);

        try {
            $this->allowanceService->delete($allowance);

            return $this->successResponse(
                null,
                'Allowance deleted successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Allowance::class);

        try {
            $allowance = $this->allowanceService->restore($uuid);

            return $this->successResponse(
                new AllowanceResource($allowance),
                'Allowance restored successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Allowance::class);

        try {
            $this->allowanceService->forceDelete($uuid);

            return $this->successResponse(
                null,
                'Allowance permanently deleted'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
