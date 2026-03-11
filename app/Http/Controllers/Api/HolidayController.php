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

/**
 * Class HolidayController
 *
 * Controller untuk mengelola data hari libur (Holiday) dalam sistem,
 * mencakup operasi CRUD untuk hari libur nasional maupun kebijakan perusahaan.
 */
class HolidayController extends Controller
{
    protected HolidayService $holidayService; /**< Instance dari HolidayService untuk logika bisnis hari libur */

    /**
     * Membuat instance HolidayController baru.
     *
     * @param HolidayService $holidayService
     */
    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    /**
     * Menampilkan daftar semua hari libur.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Holiday::class);

        $holidays = $this->holidayService->index();

        return $this->successResponse(
            HolidayResource::collection($holidays),
            'Holidays fetched successfully'
        );
    }

    /**
     * Menyimpan data hari libur baru ke database.
     *
     * @param CreateHolidayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Memperbarui data hari libur yang sudah ada.
     *
     * @param UpdateHolidayRequest $request
     * @param Holiday $holiday
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateHolidayRequest $request, Holiday $holiday): JsonResponse
    {
        $this->authorize('edit', $holiday);

        $updated = $this->holidayService->update($holiday, $request->validated(), Auth::id());

        return $this->successResponse(
            new HolidayResource($updated),
            'Holiday updated successfully'
        );
    }

    /**
     * Menghapus data hari libur dari database.
     *
     * @param Holiday $holiday
     * @return \Illuminate\Http\JsonResponse
     */
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
