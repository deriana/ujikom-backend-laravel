<?php

namespace App\Http\Controllers\Api;

use App\Exports\PayrollExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use App\Http\Resources\PayrollDetailResource;
use App\Http\Resources\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class PayrollController
 *
 * Controller untuk mengelola sistem penggajian (Payroll) karyawan,
 * mencakup pembuatan data gaji, pembaruan, finalisasi (pembayaran),
 * pembatalan (void), serta pembuatan dan pengunduhan slip gaji.
 */
class PayrollController extends Controller
{
    protected PayrollService $payrollService; /**< Instance dari PayrollService untuk logika bisnis penggajian */

    /**
     * Membuat instance PayrollController baru.
     *
     * @param PayrollService $payrollService
     */
    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Menampilkan daftar semua data payroll yang tersedia.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payroll::class);

        $filters = $request->only(['month']);

        $payrolls = $this->payrollService->index($filters);

        return $this->successResponse(
            PayrollResource::collection($payrolls),
            'Payrolls fetched successfully'
        );
    }

    /**
     * Menampilkan detail data payroll tertentu.
     *
     * @param Payroll $payroll
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Payroll $payroll): JsonResponse
    {
        $this->authorize('view', $payroll);

        $payroll = $this->payrollService->show($payroll);

        return $this->successResponse(
            new PayrollDetailResource($payroll),
            'Payroll detail fetched successfully'
        );
    }

    /**
     * Menyimpan data payroll baru ke database (biasanya berdasarkan periode tertentu).
     *
     * @param CreatePayrollRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePayrollRequest $request): JsonResponse
    {
        $this->authorize('create', Payroll::class);

        $created = $this->payrollService->store(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            PayrollResource::collection($created),
            'Payroll created successfully'
        );
    }

    /**
     * Memperbarui data payroll yang sudah ada sebelum difinalisasi.
     *
     * @param UpdatePayrollRequest $request
     * @param Payroll $payroll
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePayrollRequest $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('update', $payroll);

        $updated = $this->payrollService->update(
            $payroll,
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new PayrollResource($updated),
            'Payroll updated successfully'
        );
    }

    /**
     * Melakukan proses finalisasi payroll (menandai sebagai sudah dibayar).
     *
     * @param Payroll $payroll
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalize(Payroll $payroll): JsonResponse
    {
        $this->authorize('pay', $payroll);

        $payroll->load(['employee.user', 'employee.manager']);

        $this->payrollService->finalize($payroll);

        return $this->successResponse(
            null,
            'Payroll finalized successfully'
        );
    }

    /**
     * Melakukan proses finalisasi payroll untuk banyak data sekaligus.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkFinalize(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payroll::class);

        $request->validate([
            'payroll_uuids' => 'required|array',
            'payroll_uuids.*' => 'required|exists:payrolls,uuid',
        ]);

        $result = $this->payrollService->bulkFinalize($request->payroll_uuids);

        return $this->successResponse($result, 'Bulk payroll finalization processed');
    }

    /**
     * Membatalkan (void) data payroll yang sudah ada dengan memberikan catatan alasan.
     *
     * @param Payroll $payroll
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function void(Payroll $payroll, Request $request)
    {
        $this->authorize('update', $payroll);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $this->payrollService->void($payroll, $request->note, Auth::id());

        return $this->successResponse(null, 'Payroll voided successfully');
    }

    /**
     * Membuat file slip gaji (PDF) untuk data payroll tertentu.
     *
     * @param Payroll $payroll
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSlip(Payroll $payroll)
    {
        $payroll = $this->payrollService->generateSlip($payroll);

        return response()->json([
            'message' => 'Slip generated successfully',
            'download_url' => Storage::url($payroll->slip_path),
            'generated_at' => $payroll->slip_generated_at,
        ]);
    }

    /**
     * Mengunduh file slip gaji yang telah dibuat sebelumnya.
     *
     * @param Payroll $payroll
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadSlip(Payroll $payroll)
    {
        if (! $payroll->slip_path) {
            abort(404, 'Slip not generated.');
        }

        return Storage::download($payroll->slip_path);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Payroll::class);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $date = Carbon::parse($validated['month']);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $fileName = 'payroll_report_' . $start->format('Y-m') . '.xlsx';

        return Excel::download(
            new PayrollExport([
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ]),
            $fileName
        );
    }
}
