<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmployeeWorkScheduleRequest;
use App\Http\Requests\UpdateEmployeeWorkScheduleRequest;
use App\Http\Resources\EmployeeWorkScheduleResource;
use App\Models\EmployeeWorkSchedule;
use App\Services\EmployeeWorkScheduleService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmployeeWorkScheduleController extends Controller
{
    protected EmployeeWorkScheduleService $employeeWorkScheduleService;

    public function __construct(EmployeeWorkScheduleService $employeeWorkScheduleService)
    {
        $this->employeeWorkScheduleService = $employeeWorkScheduleService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmployeeWorkSchedule::class);

        $employeeWorkScheduleEmployeeWorkSchedules = $this->employeeWorkScheduleService->index();

        return $this->successResponse(
            EmployeeWorkScheduleResource::collection($employeeWorkScheduleEmployeeWorkSchedules),
            'EmployeeWorkSchedules fetched successfully'
        );
    }

    public function store(CreateEmployeeWorkScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeWorkSchedule::class);

        $employeeWorkScheduleEmployeeWorkSchedule = $this->employeeWorkScheduleService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employeeWorkScheduleEmployeeWorkSchedule),
            'EmployeeWorkSchedule created successfully',
            201
        );
    }

    public function show(EmployeeWorkSchedule $employeeWorkScheduleEmployeeWorkSchedule): JsonResponse
    {
        $this->authorize('view', $employeeWorkScheduleEmployeeWorkSchedule);

        $employeeWorkScheduleEmployeeWorkSchedule->load('allowances', 'creator');

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employeeWorkScheduleEmployeeWorkSchedule),
            'EmployeeWorkSchedule fetched successfully'
        );
    }

    public function update(UpdateEmployeeWorkScheduleRequest $request, EmployeeWorkSchedule $employeeWorkScheduleEmployeeWorkSchedule): JsonResponse
    {
        $this->authorize('edit', $employeeWorkScheduleEmployeeWorkSchedule);

        $updated = $this->employeeWorkScheduleService->update($employeeWorkScheduleEmployeeWorkSchedule, $request->validated(), Auth::id());

        return $this->successResponse(
            new EmployeeWorkScheduleResource($updated),
            'EmployeeWorkSchedule updated successfully'
        );
    }

    public function destroy(EmployeeWorkSchedule $employeeWorkScheduleEmployeeWorkSchedule): JsonResponse
    {
        $this->authorize('destroy', $employeeWorkScheduleEmployeeWorkSchedule);

        $this->employeeWorkScheduleService->delete($employeeWorkScheduleEmployeeWorkSchedule);

        return $this->successResponse(
            null,
            'EmployeeWorkSchedule deleted successfully'
        );
    }
}
