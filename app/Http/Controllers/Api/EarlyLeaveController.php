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

/**
 * Class EarlyLeaveController
 *
 * Controller untuk mengelola pengajuan izin pulang cepat (Early Leave) oleh karyawan,
 * mencakup proses pengajuan, pembaruan, pembatalan, persetujuan, dan pengunduhan lampiran.
 */
class EarlyLeaveController extends Controller
{
    protected EarlyLeaveService $earlyLeaveService; /**< Instance dari EarlyLeaveService untuk logika bisnis izin pulang cepat */

    /**
     * Membuat instance EarlyLeaveController baru.
     *
     * @param EarlyLeaveService $earlyLeaveService
     */
    public function __construct(EarlyLeaveService $earlyLeaveService)
    {
        $this->earlyLeaveService = $earlyLeaveService;
    }

    /**
     * Menampilkan daftar pengajuan izin pulang cepat berdasarkan role pengguna.
     *
     * @return \Illuminate\Http\JsonResponse
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
     * Menampilkan daftar pengajuan izin pulang cepat yang memerlukan persetujuan (approval).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApproval(): JsonResponse
    {
        $approvals = $this->earlyLeaveService->indexApproval(Auth::user());

        return $this->successResponse(
            EarlyLeaveResource::collection($approvals),
            'Leave approvals fetched successfully'
        );
    }

    /**
     * Menampilkan detail data pengajuan izin pulang cepat tertentu.
     *
     * @param EarlyLeave $early_leave
     * @return \Illuminate\Http\JsonResponse
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
     * Menyimpan pengajuan izin pulang cepat baru ke database.
     *
     * @param CreateEarlyLeaveRequest $request
     * @return \Illuminate\Http\JsonResponse
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
     * Memperbarui data pengajuan izin pulang cepat yang sudah ada.
     *
     * @param UpdateEarlyLeaveRequest $request
     * @param EarlyLeave $early_leave
     * @return \Illuminate\Http\JsonResponse
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
     * Menghapus atau membatalkan pengajuan izin pulang cepat (soft delete).
     *
     * @param EarlyLeave $early_leave
     * @return \Illuminate\Http\JsonResponse
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
     * Menangani proses persetujuan (approve) atau penolakan (reject) pengajuan izin pulang cepat.
     *
     * @param Request $request
     * @param EarlyLeave $early_leave
     * @return \Illuminate\Http\JsonResponse
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
     * Mengunduh file lampiran pengajuan izin pulang cepat dari penyimpanan privat.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
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
