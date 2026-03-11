<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssessmentDetailResource;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    protected AssessmentService $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Assessment::class);

        $assessments = $this->assessmentService->index();

        return $this->successResponse(
            AssessmentResource::collection($assessments),
            'Assessments fetched successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Assessment::class);

        $assessment = $this->assessmentService->store($request->all());

        return $this->successResponse(
            new AssessmentResource($assessment),
            'Assessment created successfully',
            201
        );
    }

    public function show(Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);

        $assessment = $this->assessmentService->show($assessment);

        return $this->successResponse(
            new AssessmentDetailResource($assessment),
            'Assessment fetched successfully'
        );
    }

    public function update(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('update', $assessment);

        $updated = $this->assessmentService->update($assessment, $request->all());

        return $this->successResponse(
            new AssessmentResource($updated),
            'Assessment updated successfully'
        );
    }

    public function destroy(Assessment $assessment): JsonResponse
    {
        $this->authorize('delete', $assessment);

        $this->assessmentService->delete($assessment);

        return $this->successResponse(null, 'Assessment deleted successfully');
    }
}
