<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentRequest;
use App\Http\Resources\AssessmentDetailResource;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AssessmentController
 *
 * Controller untuk mengelola penilaian kinerja (Assessment) karyawan,
 * mencakup pembuatan, pembaruan, penghapusan, dan pengambilan data penilaian.
 */
class AssessmentController extends Controller
{
    protected AssessmentService $assessmentService; /**< Instance dari AssessmentService untuk logika bisnis penilaian */

    /**
     * Membuat instance AssessmentController baru.
     *
     * @param AssessmentService $assessmentService
     */
    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Menampilkan daftar semua penilaian.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Assessment::class);

        $assessments = $this->assessmentService->index();

        return $this->successResponse(
            AssessmentResource::collection($assessments),
            'Assessments fetched successfully'
        );
    }

    /**
     * Menyimpan data penilaian baru ke database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateAssessmentRequest $request): JsonResponse
    {
        $this->authorize('create', Assessment::class);

        $assessment = $this->assessmentService->store($request->all());

        return $this->successResponse(
            new AssessmentResource($assessment),
            'Assessment created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data penilaian tertentu.
     *
     * @param Assessment $assessment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Assessment $assessment): JsonResponse
    {
        // $this->authorize('view', $assessment);

        $assessment = $this->assessmentService->show($assessment);

        return $this->successResponse(
            new AssessmentDetailResource($assessment),
            'Assessment fetched successfully'
        );
    }

    /**
     * Memperbarui data penilaian yang sudah ada.
     *
     * @param Request $request
     * @param Assessment $assessment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('update', $assessment);

        $updated = $this->assessmentService->update($assessment, $request->all());

        return $this->successResponse(
            new AssessmentResource($updated),
            'Assessment updated successfully'
        );
    }

    /**
     * Menghapus data penilaian dari database.
     *
     * @param Assessment $assessment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Assessment $assessment): JsonResponse
    {
        $this->authorize('delete', $assessment);

        $this->assessmentService->delete($assessment);

        return $this->successResponse(null, 'Assessment deleted successfully');
    }
}
