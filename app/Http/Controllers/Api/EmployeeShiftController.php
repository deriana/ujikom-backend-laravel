<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmployeeShiftRequest;
use App\Http\Requests\UpdateEmployeeShiftRequest;
use App\Http\Resources\EmployeeShiftResource;
use App\Models\EmployeeShift;
use App\Services\EmployeeShiftService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmployeeShiftController extends Controller
{
    protected EmployeeShiftService $employeeShiftService;

    public function __construct(EmployeeShiftService $employeeShiftService)
    {
        $this->employeeShiftService = $employeeShiftService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmployeeShift::class);

        $employee_shifts = $this->employeeShiftService->index();

        return $this->successResponse(
            EmployeeShiftResource::collection($employee_shifts),
            'EmployeeShifts fetched successfully'
        );
    }

    public function store(CreateEmployeeShiftRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeShift::class);

        $employee_shift = $this->employeeShiftService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new EmployeeShiftResource($employee_shift),
            'EmployeeShift created successfully',
            201
        );
    }

    public function show(EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('view', $employee_shift);

        $employee_shift->load('allowances', 'creator');

        return $this->successResponse(
            new EmployeeShiftResource($employee_shift),
            'EmployeeShift fetched successfully'
        );
    }

    public function update(UpdateEmployeeShiftRequest $request, EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('edit', $employee_shift);

        $updated = $this->employeeShiftService->update($employee_shift, $request->validated(), Auth::id());

        return $this->successResponse(
            new EmployeeShiftResource($updated),
            'EmployeeShift updated successfully'
        );
    }

    public function destroy(EmployeeShift $employee_shift): JsonResponse
    {
        $this->authorize('destroy', $employee_shift);

        $deleted = $this->employeeShiftService->delete($employee_shift);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete EmployeeShift', 500);
        }

        return $this->successResponse(null, 'EmployeeShift deleted successfully');
    }
}
