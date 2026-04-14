<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePointRequest;
use App\Http\Resources\PointResource;
use App\Models\PointTransaction;
use App\Services\PointService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class PointController extends Controller
{
        protected PointService $pointService; /**< Instance dari PointService untuk logika bisnis penilaian */

    /**
     * Membuat instance PointController baru.
     *
     * @param PointService $pointService
     */
    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    /**
     * Menampilkan daftar semua penilaian.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PointTransaction::class);

        $points = $this->pointService->index();

        return $this->successResponse(
            PointResource::collection($points),
            'Points fetched successfully'
        );
    }

    /**
     * Menyimpan data penilaian baru ke database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePointRequest $request): JsonResponse
    {
        $this->authorize('create', PointTransaction::class);

        $point = $this->pointService->store($request->all());

        return $this->successResponse(
            new PointResource($point),
            'Point created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data penilaian tertentu.
     *
     * @param PointTransaction $point
     * @return \Illuminate\Http\JsonResponse
     */
    // public function show(PointTransaction $point): JsonResponse
    // {
    //     // $this->authorize('view', $point);

    //     $point = $this->pointService->show($point);

    //     return $this->successResponse(
    //         new PointDetailResource($point),
    //         'Point fetched successfully'
    //     );
    // }

    /**
     * Memperbarui data penilaian yang sudah ada.
     *
     * @param Request $request
     * @param PointTransaction $point
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PointTransaction $point): JsonResponse
    {
        $this->authorize('update', $point);

        $updated = $this->pointService->update($point, $request->all());

        return $this->successResponse(
            new PointResource($updated),
            'Point updated successfully'
        );
    }

    /**
     * Menghapus data penilaian dari database.
     *
     * @param PointTransaction $point
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PointTransaction $point): JsonResponse
    {
        $this->authorize('delete', $point);

        $this->pointService->delete($point);

        return $this->successResponse(null, 'Point deleted successfully');
    }
}
