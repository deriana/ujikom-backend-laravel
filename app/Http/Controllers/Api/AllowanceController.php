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

/**
 * Class AllowanceController
 *
 * Controller untuk mengelola data tunjangan (Allowance), termasuk pembuatan,
 * pembaruan, penghapusan, dan pemulihan data tunjangan.
 */
class AllowanceController extends Controller
{
    protected AllowanceService $allowanceService; /**< Instance dari AllowanceService untuk logika bisnis tunjangan */

    /**
     * Membuat instance AllowanceController baru.
     *
     * @param AllowanceService $allowanceService
     */
    public function __construct(AllowanceService $allowanceService)
    {
        $this->allowanceService = $allowanceService;
    }

    /**
     * Menampilkan daftar semua tunjangan yang tersedia.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Allowance::class);

        $allowances = $this->allowanceService->index();

        return $this->successResponse(
            AllowanceResource::collection($allowances),
            'Allowances fetched successfully'
        );
    }

    /**
     * Menyimpan data tunjangan baru ke database.
     *
     * @param CreateAllowanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Menampilkan detail data tunjangan tertentu.
     *
     * @param Allowance $allowance
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Allowance $allowance): JsonResponse
    {
        $this->authorize('view', $allowance);

        $allowance->load(['creator', 'positions']);

        return $this->successResponse(
            new AllowanceResource($allowance),
            'Allowance fetched successfully'
        );
    }

    /**
     * Memperbarui data tunjangan yang sudah ada.
     *
     * @param UpdateAllowanceRequest $request
     * @param Allowance $allowance
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAllowanceRequest $request, Allowance $allowance): JsonResponse
    {
        $this->authorize('edit', $allowance);

        $updated = $this->allowanceService->update($allowance, $request->validated(), Auth::id());

        return $this->successResponse(
            new AllowanceResource($updated),
            'Allowance updated successfully'
        );
    }

    /**
     * Menghapus data tunjangan (Soft Delete).
     *
     * @param Allowance $allowance
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Allowance $allowance): JsonResponse
    {
        $this->authorize('destroy', $allowance);

        $this->allowanceService->delete($allowance);

        return $this->successResponse(
            null,
            'Allowance deleted successfully'
        );
    }

    /**
     * Memulihkan data tunjangan yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Allowance::class);

        $allowance = $this->allowanceService->restore($uuid);

        return $this->successResponse(
            new AllowanceResource($allowance),
            'Allowance restored successfully'
        );
    }

    /**
     * Menghapus data tunjangan secara permanen dari database.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Allowance::class);

        $this->allowanceService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'Allowance permanently deleted'
        );
    }

    /**
     * Mengambil daftar tunjangan yang berada di dalam trash (terhapus sementara).
     *
     * @return \Illuminate\Http\JsonResponse
     */
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
