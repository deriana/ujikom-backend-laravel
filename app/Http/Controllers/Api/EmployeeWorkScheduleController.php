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

        $employee_work_schedules = $this->employeeWorkScheduleService->index();

        return $this->successResponse(
            EmployeeWorkScheduleResource::collection($employee_work_schedules),
            'EmployeeWorkSchedules fetched successfully'
        );
    }

    public function store(CreateEmployeeWorkScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeWorkSchedule::class);

        $employee_work_schedule = $this->employeeWorkScheduleService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employee_work_schedule),
            'EmployeeWorkSchedule created successfully',
            201
        );
    }

    public function show(EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('view', $employee_work_schedule);

        $employee_work_schedule->load('allowances', 'creator');

        return $this->successResponse(
            new EmployeeWorkScheduleResource($employee_work_schedule),
            'EmployeeWorkSchedule fetched successfully'
        );
    }

    public function update(UpdateEmployeeWorkScheduleRequest $request, EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('edit', $employee_work_schedule);

        $updated = $this->employeeWorkScheduleService->update($employee_work_schedule, $request->validated(), Auth::id());

        return $this->successResponse(
            new EmployeeWorkScheduleResource($updated),
            'EmployeeWorkSchedule updated successfully'
        );
    }

    public function destroy(EmployeeWorkSchedule $employee_work_schedule): JsonResponse
    {
        $this->authorize('destroy', $employee_work_schedule);

        $deleted = $this->employeeWorkScheduleService->delete($employee_work_schedule);

        if (! $deleted) {
            return $this->errorResponse('Failed to delete EmployeeWorkSchedule', 500);
        }

        return $this->successResponse(null, 'EmployeeWorkSchedule deleted successfully');
    }
}
