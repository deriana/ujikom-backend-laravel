<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAllowanceRequest;
use App\Http\Requests\UpdateAllowanceRequest;
use App\Http\Resources\AllowanceResource;
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

        $allowances = $this->allowanceService->index();

        return $this->successResponse(
            AllowanceResource::collection($allowances),
            'Allowances fetched successfully'
        );
    }

    public function store(CreateAllowanceRequest $request): JsonResponse
    {
        $this->authorize('create', Allowance::class);

        $allowance = $this->allowanceService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new AllowanceResource($allowance),
            'Allowance created successfully',
            201
        );
    }

    public function show(Allowance $allowance): JsonResponse
    {
        $this->authorize('view', $allowance);

        $allowance->load(['creator', 'positions']);

        return $this->successResponse(
            new AllowanceResource($allowance),
            'Allowance fetched successfully'
        );
    }

    public function update(UpdateAllowanceRequest $request, Allowance $allowance): JsonResponse
    {
        $this->authorize('edit', $allowance);

        $updated = $this->allowanceService->update($allowance, $request->validated(), Auth::id());

        return $this->successResponse(
            new AllowanceResource($updated),
            'Allowance updated successfully'
        );
    }

    public function destroy(Allowance $allowance): JsonResponse
    {
        $this->authorize('destroy', $allowance);

        $this->allowanceService->delete($allowance);

        return $this->successResponse(
            null,
            'Allowance deleted successfully'
        );
    }

    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Allowance::class);

        $allowance = $this->allowanceService->restore($uuid);

        return $this->successResponse(
            new AllowanceResource($allowance),
            'Allowance restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Allowance::class);

        $this->allowanceService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'Allowance permanently deleted'
        );
    }

    public function getTrashed()
    {
        $this->authorize('restore', Allowance::class);

        $allowances = $this->allowanceService->getTrashed();

        return $this->successResponse(
            AllowanceResource::collection($allowances),
            'Trashed Allowances fetched successfully'
        );
    }
}
