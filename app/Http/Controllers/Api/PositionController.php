<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Class PositionController
 *
 * Controller untuk mengelola data jabatan (Position) dalam organisasi,
 * mencakup operasi CRUD, pemulihan data terhapus, dan manajemen tunjangan terkait jabatan.
 */
class PositionController extends Controller
{
    protected PositionService $positionService; /**< Instance dari PositionService untuk logika bisnis jabatan */

    /**
     * Membuat instance PositionController baru.
     *
     * @param PositionService $positionService
     */
    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Menampilkan daftar semua jabatan yang tersedia.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $positions = $this->positionService->index();

        return $this->successResponse(
            PositionResource::collection($positions),
            'Positions fetched successfully'
        );
    }

    /**
     * Menyimpan data jabatan baru ke database.
     *
     * @param CreatePositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = $this->positionService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new PositionResource($position),
            'Position created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data jabatan tertentu beserta relasi tunjangannya.
     *
     * @param Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        $position->load('allowances', 'creator');

        return $this->successResponse(
            new PositionResource($position),
            'Position fetched successfully'
        );
    }

    /**
     * Memperbarui data jabatan yang sudah ada.
     *
     * @param UpdatePositionRequest $request
     * @param Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('edit', $position);

        $updated = $this->positionService->update($position, $request->validated(), Auth::id());

        return $this->successResponse(
            new PositionResource($updated),
            'Position updated successfully'
        );
    }

    /**
     * Menghapus data jabatan (Soft Delete).
     *
     * @param Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('destroy', $position);

        $this->positionService->delete($position);

        return $this->successResponse(
            null,
            'Position deleted successfully'
        );
    }

    /**
     * Memulihkan data jabatan yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Position::class);

        $position = $this->positionService->restore($uuid);

        return $this->successResponse(
            new PositionResource($position),
            'Position restored successfully'
        );
    }

    /**
     * Menghapus data jabatan secara permanen dari database.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Position::class);

        $this->positionService->forceDelete($uuid);

        return $this->successResponse(
            null,
            'Position permanently deleted'
        );
    }

    /**
     * Mengambil daftar jabatan yang berada di dalam trash (terhapus sementara).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrashed()
    {
        $this->authorize('restore', Position::class);

        $allowances = $this->positionService->getTrashed();

        return $this->successResponse(
            PositionResource::collection($allowances),
            'Trashed Positions fetched successfully'
        );
    }
}
