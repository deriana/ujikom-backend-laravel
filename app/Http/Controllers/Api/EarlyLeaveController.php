<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEarlyLeaveRequest;
use App\Http\Requests\UpdateEarlyLeaveRequest;
use App\Http\Resources\EarlyLeaveResource;
use App\Models\EarlyLeave;
use App\Services\EarlyLeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EarlyLeaveController extends Controller
{
    protected EarlyLeaveService $earlyLeaveService;

    public function __construct(EarlyLeaveService $earlyLeaveService)
    {
        $this->earlyLeaveService = $earlyLeaveService;
    }

    /**
     * Index / list leaves
     */
    public function index(): JsonResponse
    {
        $earlyLeaves = $this->earlyLeaveService->index(Auth::user());

        return $this->successResponse(
            EarlyLeaveResource::collection($earlyLeaves),
            'Leaves fetched successfully'
        );
    }

    /**
     * Show detail leave
     */
    public function show(EarlyLeave $early_leave): JsonResponse
    {
        $this->authorize('view', $early_leave);

        $detail = $this->earlyLeaveService->show($early_leave);

        return $this->successResponse(
            $detail,
            'EarlyLeave details fetched successfully'
        );
    }

    /**
     * Create earlyLeave
     */
    public function store(CreateEarlyLeaveRequest $request): JsonResponse
    {
        $earlyLeave = $this->earlyLeaveService->store(
            $request->all(),
            Auth::user()
        );

        return $this->successResponse(
            new EarlyLeaveResource($earlyLeave),
            'EarlyLeave created successfully',
            201
        );
    }

    /**
     * Update earlyLeave
     */
    public function update(UpdateEarlyLeaveRequest $request, EarlyLeave $early_leave): JsonResponse
    {
        $this->authorize('update', $early_leave);

        $updated = $this->earlyLeaveService->update($early_leave, $request->validated(), Auth::user());

        return $this->successResponse(
            new EarlyLeaveResource($updated),
            'EarlyLeave updated successfully'
        );
    }

    /**
     * Delete / cancel earlyLeave (soft delete)
     */
    public function destroy(EarlyLeave $early_leave): JsonResponse
    {
        $this->authorize('delete', $early_leave);

        $this->earlyLeaveService->delete($early_leave, Auth::user());

        return $this->successResponse(
            null,
            'EarlyLeave deleted successfully'
        );
    }

    /**
     * Approval
     */
    public function approve(Request $request, EarlyLeave $early_leave): JsonResponse
    {
        // 1. Validasi input (Sangat disarankan)
        $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        // 2. Authorize
        $this->authorize('approve', $early_leave);

        // 3. Ambil nilai dari request
        $approve = $request->input('approve');
        $note = $request->input('note');


        // 4. Panggil service
        $updated = $this->earlyLeaveService->approve($early_leave, Auth::user(), $approve, $note);

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
        $path = 'private/early_leave_attachments/'.$filename;

        if (! Storage::exists($path)) {
            abort(404, 'File not found');
        }

        return Storage::download($path);
    }
}
