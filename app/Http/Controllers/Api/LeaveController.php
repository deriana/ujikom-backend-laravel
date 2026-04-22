<?php

namespace App\Http\Controllers\Api;

use App\Exports\LeaveExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LeaveController
 *
 * Controller untuk mengelola pengajuan cuti (Leave) karyawan, mencakup proses pengajuan,
 * pembaruan, pembatalan, persetujuan berjenjang, dan pengunduhan lampiran medis/pendukung.
 */
class LeaveController extends Controller
{
    protected LeaveService $leaveService; /**< Instance dari LeaveService untuk logika bisnis cuti */

    /**
     * Membuat instance LeaveController baru.
     *
     * @param LeaveService $leaveService
     */
    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Menampilkan daftar semua pengajuan cuti berdasarkan role pengguna.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date']);

        $leaves = $this->leaveService->index(Auth::user(), $filters);

        return $this->successResponse(
            $leaves,
            'Leaves fetched successfully'
        );
    }

    /**
     * Menampilkan daftar pengajuan cuti milik karyawan yang sedang login.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myLeaves()
    {
        $leaves = $this->leaveService->myLeaves(Auth::user());

        return $this->successResponse(
            $leaves,
            'Leaves fetched successfully'
        );
    }

    /**
     * Menampilkan detail data pengajuan cuti tertentu.
     *
     * @param Leave $leave
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(Leave $leave): JsonResponse
    {
        $this->authorize('view', $leave);

        // Log::info($leave);

        $detail = $this->leaveService->show($leave);

        return $this->successResponse(
            $detail,
            'Leave details fetched successfully'
        );
    }

    /**
     * Menyimpan pengajuan cuti baru ke database.
     *
     * @param CreateLeaveRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreateLeaveRequest $request): JsonResponse
    {
        Log::info("INI LOG PERTAMA");
        Log::info('Data Request Masuk:', $request->all());

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
     * Memperbarui data pengajuan cuti yang sudah ada.
     *
     * @param UpdateLeaveRequest $request
     * @param Leave $leave
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menghapus atau membatalkan pengajuan cuti (soft delete).
     *
     * @param Leave $leave
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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

    /**
     * Menampilkan daftar pengajuan cuti yang memerlukan persetujuan (approval) dari atasan.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexApproval(): JsonResponse
    {
        $approvals = $this->leaveService->indexApprovals(Auth::user());

        return $this->successResponse(
            LeaveResource::collection($approvals),
            'Leave approvals fetched successfully'
        );
    }

    /**
     * Menangani proses persetujuan (approve) atau penolakan (reject) pada tahapan approval tertentu.
     *
     * @param Request $request
     * @param LeaveApproval $approval
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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

        // Log::info('Data Request Masuk:', $request->all());

        // 4. Panggil service
        $updated = $this->leaveService->approve($approval, Auth::user(), $approve, $note);

        return $this->successResponse(
            $updated,
            $approve ? 'Leave approved successfully' : 'Leave rejected successfully'
        );
    }

    /**
     * Mengunduh file lampiran pengajuan cuti dari penyimpanan privat.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadAttachment(string $filename)
    {
        $path = 'private/leave_attachments/'.$filename;

        if (! Storage::exists($path)) {
            abort(404, 'File not found');
        }

        return Storage::download($path);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Leave::class);

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);


        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now()->endOfDay();

        // if ($start->diffInDays($end) > 31) {
        //     throw ValidationException::withMessages([
        //         'end_date' => 'Maximum export range is 31 days.',
        //     ]);
        // }

        $fileName = 'leaves_' . $start->format('Y-m-d') . '_to_' . $end->format('Y-m-d') . '.xlsx';
        // Log::info($fileName);

        return Excel::download(
            new LeaveExport($validated),
            $fileName
        );
    }
}
