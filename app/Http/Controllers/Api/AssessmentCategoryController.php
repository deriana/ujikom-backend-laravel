<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentCategoryRequest;
use App\Http\Requests\UpdateAssessmentCategoryRequest;
use App\Http\Resources\AssessmentCategoryResource;
use App\Models\AssessmentCategory;
use App\Services\AssessmentCategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AssessmentCategoryController
 *
 * Controller untuk mengelola kategori penilaian (Assessment Category),
 * mencakup operasi CRUD dan pengaturan status aktif/non-aktif.
 */
class AssessmentCategoryController extends Controller
{
    protected AssessmentCategoryService $assessmentCategoryService; /**< Instance dari AssessmentCategoryService untuk logika bisnis kategori penilaian */

    /**
     * Membuat instance AssessmentCategoryController baru.
     *
     * @param AssessmentCategoryService $assessmentCategoryService
     */
    public function __construct(AssessmentCategoryService $assessmentCategoryService)
    {
        $this->assessmentCategoryService = $assessmentCategoryService;
    }

    /**
     * Menampilkan daftar semua kategori penilaian.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AssessmentCategory::class);

        $categories = $this->assessmentCategoryService->index();

        return $this->successResponse(
            AssessmentCategoryResource::collection($categories),
            'Assessment categories fetched successfully'
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
     * @param CreateAssessmentCategoryRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateAssessmentCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', AssessmentCategory::class);

        $category = $this->assessmentCategoryService->store($request->validated());

        return $this->successResponse(
            new AssessmentCategoryResource($category),
            'Assessment category created successfully',
            201
        );
    }

    /**
     * Memperbarui data kategori penilaian yang sudah ada.
     *
     * @param UpdateAssessmentCategoryRequest $request
     * @param AssessmentCategory $assessment_category
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update(UpdateAssessmentCategoryRequest $request, AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('update', $assessment_category);

        $updated = $this->assessmentCategoryService->update($assessment_category, $request->validated());

        return $this->successResponse(
            new AssessmentCategoryResource($updated),
            'Assessment category updated successfully'
        );
    }

    /**
     * Menghapus kategori penilaian dari database.
     *
     * @param AssessmentCategory $assessment_category
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('delete', $assessment_category);

        $this->assessmentCategoryService->delete($assessment_category);

        return $this->successResponse(null, 'Assessment category deleted successfully');
    }

    /**
     * Mengubah status aktif/non-aktif kategori penilaian.
     *
     * @param AssessmentCategory $assessment_category
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function toggleStatus(AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('update', $assessment_category);

        $this->assessmentCategoryService->toggleStatus($assessment_category);

        return $this->successResponse(null, 'Assessment category status updated successfully');
    }
}
