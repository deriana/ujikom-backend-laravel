<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Http\Resources\OvertimeDetailResource;
use App\Http\Resources\OvertimeResource;
use App\Models\Overtime;
use App\Services\OvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class OvertimeController extends Controller
{
    protected OvertimeService $overtimeService;

    public function __construct(OvertimeService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    /**
     * List semua overtime sesuai role
     */
    public function index(): JsonResponse
    {
        $overtimes = $this->overtimeService->index(Auth::user());

        return $this->successResponse(
            OvertimeResource::collection($overtimes),
            'Overtimes fetched successfully'
        );
    }

    /**
     * List overtime yang perlu diapprove
     */
    public function indexApproval(): JsonResponse
    {
        $approvals = $this->overtimeService->indexApproval(Auth::user());

        return $this->successResponse(
            OvertimeResource::collection($approvals),
            'Overtime approvals fetched successfully'
        );
    }

    /**
     * Detail overtime
     */
    public function show(Overtime $overtime): JsonResponse
    {
        $this->authorize('view', $overtime);

        $detail = $this->overtimeService->show($overtime);

        return $this->successResponse(
            new OvertimeDetailResource($detail),
            'Overtime details fetched successfully'
        );
    }

    /**
     * Create / ajukan overtime
     */
    public function store(CreateOvertimeRequest $request): JsonResponse
    {
        $this->authorize('create', Overtime::class);

        $overtime = $this->overtimeService->store(Auth::user(), $request->all());

        return $this->successResponse(
            new OvertimeResource($overtime),
            'Overtime created successfully',
            201
        );
    }

    /**
     * Update overtime
     */
    public function update(UpdateOvertimeRequest $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('update', $overtime);

        $updated = $this->overtimeService->update($overtime, $request->validated(), Auth::user());

        return $this->successResponse(
            new OvertimeResource($updated),
            'Overtime updated successfully'
        );
    }

    /**
     * Delete / cancel overtime
     */
    public function destroy(Overtime $overtime): JsonResponse
    {
        $this->authorize('delete', $overtime);

        $this->overtimeService->delete($overtime);

        return $this->successResponse(
            null,
            'Overtime deleted successfully'
        );
    }

    /**
     * Approve / reject overtime
     */
    public function approve(Request $request, Overtime $overtime): JsonResponse
    {
        $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        $this->authorize('approve', $overtime);

        $updated = $this->overtimeService->approve(
            $overtime,
            Auth::user(),
            $request->input('approve'),
            $request->input('note')
        );

        return $this->successResponse(
            new OvertimeResource($updated),
            $request->input('approve') ? 'Overtime approved successfully' : 'Overtime rejected successfully'
        );
    }
}
