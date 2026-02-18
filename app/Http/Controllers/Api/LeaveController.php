<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;

class LeaveController extends Controller
{
    protected LeaveService $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Index / list leaves
     */
    public function index(): JsonResponse
    {
        $leaves = $this->leaveService->index(Auth::user());

        return $this->successResponse(
            $leaves,
            'Leaves fetched successfully'
        );
    }

    /**
     * Show detail leave
     */
    public function show(Leave $leave): JsonResponse
    {
        $this->authorize('view', $leave);

        Log::info($leave);

        $detail = $this->leaveService->show($leave);

        return $this->successResponse(
            $detail,
            'Leave details fetched successfully'
        );
    }

    /**
     * Create leave
     */
    public function store(CreateLeaveRequest $request): JsonResponse
    {
        $leave = $this->leaveService->store(
            $request->all(),
            Auth::user()
        );

        return $this->successResponse(
            new LeaveResource($leave),
            'Leave created successfully',
            201
        );
    }

    /**
     * Update leave
     */
    public function update(UpdateLeaveRequest $request, Leave $leave): JsonResponse
    {
        $this->authorize('update', $leave);

        $updated = $this->leaveService->update($leave, $request->validated(), Auth::user());

        return $this->successResponse(
            new LeaveResource($updated),
            'Leave updated successfully'
        );
    }

    /**
     * Delete / cancel leave (soft delete)
     */
    public function destroy(Leave $leave): JsonResponse
    {
        $this->authorize('delete', $leave);

        $this->leaveService->delete($leave, Auth::user());

        return $this->successResponse(
            null,
            'Leave deleted successfully'
        );
    }

    public function indexApproval(): JsonResponse
    {
        $approvals = $this->leaveService->indexApprovals(Auth::user());

        return $this->successResponse(
            LeaveResource::collection($approvals),
            'Leave approvals fetched successfully'
        );
    }

    /**
     * Approval
     */
    public function approve(Request $request, LeaveApproval $approval): JsonResponse
    {
        // 1. Validasi input (Sangat disarankan)
        $validated = $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        // 2. Authorize
        $this->authorize('approve', $approval->leave);

        // 3. Ambil nilai dari request
        $approve = $request->input('approve');
        $note = $request->input('note');

        Log::info('Data Request Masuk:', $request->all());

        // 4. Panggil service
        $updated = $this->leaveService->approve($approval, Auth::user(), $approve, $note);

        return $this->successResponse(
            $updated,
            $approve ? 'Leave approved successfully' : 'Leave rejected successfully'
        );
    }

    /**
     * Download attachment (private storage)
     */
    public function downloadAttachment(string $filename)
    {
        $path = 'private/leave_attachments/'.$filename;

        if (! Storage::exists($path)) {
            abort(404, 'File not found');
        }

        return Storage::download($path);
    }
}
