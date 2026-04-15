<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePointRuleRequest;
use App\Http\Requests\UpdatePointRuleRequest;
use App\Http\Resources\PointRuleResource;
use App\Models\PointRule;
use App\Services\PointRuleService;
use Symfony\Component\HttpFoundation\JsonResponse;

class PointRuleController extends Controller
{
        protected PointRuleService $pointRuleService; /**< Instance dari PointRuleService untuk logika bisnis kategori penilaian */

    /**
     * Membuat instance PointRuleController baru.
     *
     * @param PointRuleService $pointRuleService
     */
    public function __construct(PointRuleService $pointRuleService)
    {
        $this->pointRuleService = $pointRuleService;
    }

    /**
     * Menampilkan daftar semua kategori penilaian.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PointRule::class);

        $categories = $this->pointRuleService->index();

        return $this->successResponse(
            PointRuleResource::collection($categories),
            'Point Rules fetched successfully'
        );
    }

    /**
     * Menampilkan detail kategori penilaian tertentu.
     */
    public function show()
    {
        // show
    }

    /**
     * Menyimpan kategori penilaian baru ke database.
     *
     * @param CreatePointRuleRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreatePointRuleRequest $request): JsonResponse
    {
        $this->authorize('create', PointRule::class);

        $category = $this->pointRuleService->store($request->validated());

        return $this->successResponse(
            new PointRuleResource($category),
            'Point Rule created successfully',
            201
        );
    }

    /**
     * Memperbarui data aturan poin yang sudah ada.
     *
     * @param UpdatePointRuleRequest $request
     * @param PointRule $pointRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePointRuleRequest $request, PointRule $pointRule): JsonResponse
    {
        // Nama variabel harus $pointRule agar Laravel bisa nge-bind UUID dari URL ke Model
        $this->authorize('update', $pointRule);

        $updated = $this->pointRuleService->update($pointRule, $request->validated());

        return $this->successResponse(
            new PointRuleResource($updated),
            'Point Rule updated successfully'
        );
    }
    /**
     * Menghapus kategori penilaian dari database.
     *
     * @param PointRule $pointRule
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(PointRule $pointRule): JsonResponse
    {
        $this->authorize('delete', $pointRule);

        $this->pointRuleService->delete($pointRule);

        return $this->successResponse(null, 'Point Rule deleted successfully');
    }

    /**
     * Mengubah status aktif/non-aktif kategori penilaian.
     *
     * @param PointRule $point_rules
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function toggleStatus(PointRule $point_rules): JsonResponse
    {
        $this->authorize('update', $point_rules);

        $this->pointRuleService->toggleStatus($point_rules);

        return $this->successResponse(null, 'Point Rule status updated successfully');
    }
}
