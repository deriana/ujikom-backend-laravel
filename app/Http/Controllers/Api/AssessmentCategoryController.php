<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentCategoryRequest;
use App\Http\Requests\UpdateAssessmentCategoryRequest;
use App\Http\Resources\AssessmentCategoryResource;
use App\Models\AssessmentCategory;
use App\Services\AssessmentCategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssessmentCategoryController extends Controller
{
    protected AssessmentCategoryService $assessmentCategoryService;

    public function __construct(AssessmentCategoryService $assessmentCategoryService)
    {
        $this->assessmentCategoryService = $assessmentCategoryService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AssessmentCategory::class);

        $categories = $this->assessmentCategoryService->index();

        return $this->successResponse(
            AssessmentCategoryResource::collection($categories),
            'Assessment categories fetched successfully'
        );
    }

    public function show()
    {
        // show
    }

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

    public function update(UpdateAssessmentCategoryRequest $request, AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('update', $assessment_category);

        $updated = $this->assessmentCategoryService->update($assessment_category, $request->validated());

        return $this->successResponse(
            new AssessmentCategoryResource($updated),
            'Assessment category updated successfully'
        );
    }

    public function destroy(AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('delete', $assessment_category);

        $this->assessmentCategoryService->delete($assessment_category);

        return $this->successResponse(null, 'Assessment category deleted successfully');
    }

    public function toggleStatus(AssessmentCategory $assessment_category): JsonResponse
    {
        $this->authorize('update', $assessment_category);

        $this->assessmentCategoryService->toggleStatus($assessment_category);

        return $this->successResponse(null, 'Assessment category status updated successfully');
    }
}
