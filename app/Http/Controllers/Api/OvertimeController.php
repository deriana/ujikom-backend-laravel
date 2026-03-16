<?php

namespace App\Http\Controllers\Api;

use App\Exports\OvertimeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Http\Resources\OvertimeDetailResource;
use App\Http\Resources\OvertimeResource;
use App\Models\Overtime;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class OvertimeController
 *
 * Controller untuk mengelola pengajuan lembur (Overtime) karyawan,
 * mencakup proses pengajuan, pembaruan, pembatalan, dan persetujuan (approval).
 */
class OvertimeController extends Controller
{
    protected OvertimeService $overtimeService; /**< Instance dari OvertimeService untuk logika bisnis lembur */

    /**
     * Membuat instance OvertimeController baru.
     *
     * @param OvertimeService $overtimeService
     */
    public function __construct(OvertimeService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    /**
     * Menampilkan daftar semua pengajuan lembur berdasarkan role pengguna.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date']);

        $overtimes = $this->overtimeService->index(Auth::user(), $filters);

        return $this->successResponse(
            OvertimeResource::collection($overtimes),
            'Overtimes fetched successfully'
        );
    }

    /**
     * Menampilkan daftar pengajuan lembur yang memerlukan persetujuan (approval).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menampilkan detail data pengajuan lembur tertentu.
     *
     * @param Overtime $overtime
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menyimpan pengajuan lembur baru ke database.
     *
     * @param CreateOvertimeRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Memperbarui data pengajuan lembur yang sudah ada.
     *
     * @param UpdateOvertimeRequest $request
     * @param Overtime $overtime
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menghapus atau membatalkan pengajuan lembur (soft delete).
     *
     * @param Overtime $overtime
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * Menangani proses persetujuan (approve) atau penolakan (reject) pengajuan lembur.
     *
     * @param Request $request
     * @param Overtime $overtime
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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

    public function export(Request $request)
    {
        $this->authorize('export', Overtime::class);

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

        $fileName = 'overtimes_' . $start->format('Y-m-d') . '_to_' . $end->format('Y-m-d') . '.xlsx';
        // Log::info($fileName);

        return Excel::download(
            new OvertimeExport($validated),
            $fileName
        );
    }
}
