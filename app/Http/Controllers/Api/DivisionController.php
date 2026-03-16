<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDivisionRequest;
use App\Http\Requests\UpdateDivisionRequest;
use App\Http\Resources\DivisionResource;
use App\Http\Resources\DivisionTeamEmployeeResource;
use App\Models\Division;
use App\Services\DivisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Class DivisionController
 *
 * Controller untuk mengelola data divisi dalam organisasi, mencakup operasi CRUD,
 * pemulihan data yang dihapus, serta pengambilan struktur divisi beserta tim dan karyawan.
 */
class DivisionController extends Controller
{
    protected DivisionService $divisionService; /**< Instance dari DivisionService untuk logika bisnis divisi */

    /**
     * Membuat instance DivisionController baru.
     *
     * @param DivisionService $divisionService
     */
    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService = $divisionService;
    }

    /**
     * Menampilkan daftar semua divisi.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Division::class);

        $divisions = $this->divisionService->index();

        return $this->successResponse(
            DivisionResource::collection($divisions),
            'Divisions fetched successfully'
        );
    }

    /**
     * Mengambil data divisi lengkap dengan relasi tim dan karyawannya.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDivisionsWithTeamsAndEmployees(): JsonResponse
    {
        $divisions = $this->divisionService->getDivisionsWithTeamsAndEmployees();

        return $this->successResponse(
            DivisionTeamEmployeeResource::collection($divisions),
            'Divisions fetched successfully'
        );
    }

    /**
     * Menyimpan data divisi baru ke database.
     *
     * @param CreateDivisionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateDivisionRequest $request): JsonResponse
    {
        $this->authorize('create', Division::class);

        $division = $this->divisionService->store($request->validated(), Auth::id());

        return $this->successResponse(
            new DivisionResource($division->load('teams')),
            'Division created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data divisi tertentu.
     *
     * @param Division $division
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Division $division): JsonResponse
    {
        $this->authorize('view', $division);

        return $this->successResponse(
            new DivisionResource($division->load('teams', 'creator')),
            'Division fetched successfully'
        );
    }

    /**
     * Memperbarui data divisi yang sudah ada.
     *
     * @param UpdateDivisionRequest $request
     * @param Division $division
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDivisionRequest $request, Division $division): JsonResponse
    {
        $this->authorize('edit', $division);

        $updated = $this->divisionService->update($division, $request->validated(), Auth::id());

        return $this->successResponse(
            new DivisionResource($updated->load('teams', 'creator')),
            'Division updated successfully'
        );
    }

    /**
     * Menghapus data divisi (Soft Delete).
     *
     * @param Division $division
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Division $division): JsonResponse
    {
        $this->authorize('destroy', $division);

        $this->divisionService->delete($division);

        return $this->successResponse(null, 'Division deleted successfully');
    }

    /**
     * Memulihkan data divisi yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', Division::class);

        $division = $this->divisionService->restore($uuid);

        return $this->successResponse(
            new DivisionResource($division->load('teams')),
            'Division restored successfully'
        );
    }

    /**
     * Menghapus data divisi secara permanen dari database.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', Division::class);

        $this->divisionService->forceDelete($uuid);

        return $this->successResponse(null, 'Division permanently deleted');
    }

    /**
     * Mengambil daftar divisi yang berada di dalam trash (terhapus sementara).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrashed(): JsonResponse
    {
        $this->authorize('restore', Division::class);

        $divisions = $this->divisionService->getTrashed();

        return $this->successResponse(
            DivisionResource::collection($divisions),
            'Trashed Divisions fetched successfully'
        );
    }
}
