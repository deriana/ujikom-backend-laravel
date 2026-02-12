<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    protected HolidayService $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Holiday::class);

        $holidays = $this->holidayService->index();

        return $this->successResponse(
            HolidayResource::collection($holidays),
            'Holidays fetched successfully'
        );
    }

    public function store(CreateHolidayRequest $request): JsonResponse
    {
        $this->authorize('create', Holiday::class);

        $holiday = $this->holidayService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new HolidayResource($holiday),
            'Holiday created successfully',
            201
        );
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): JsonResponse
    {
        $this->authorize('edit', $holiday);

        $updated = $this->holidayService->update($holiday, $request->validated(), Auth::id());

        return $this->successResponse(
            new HolidayResource($updated),
            'Holiday updated successfully'
        );
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->authorize('destroy', $holiday);

        $this->holidayService->delete($holiday);

        return $this->successResponse(
            null,
            'Holiday deleted successfully'
        );
    }
}
