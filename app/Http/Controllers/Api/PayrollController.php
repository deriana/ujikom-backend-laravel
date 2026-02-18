<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePayrollRequest;
use App\Http\Resources\PayrollDetailResource;
use App\Http\Resources\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payroll::class);

        $payrolls = $this->payrollService->index();

        return $this->successResponse(
            PayrollResource::collection($payrolls),
            'Payrolls fetched successfully'
        );
    }

    public function show(Payroll $payroll): JsonResponse
    {
        $this->authorize('view', $payroll);

        $payroll = $this->payrollService->show($payroll);

        return $this->successResponse(
            new PayrollDetailResource($payroll),
            'Payroll detail fetched successfully'
        );
    }

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

    public function finalize(Payroll $payroll): JsonResponse
    {
        $this->authorize('pay', $payroll);

        $finalized = $this->payrollService->finalize($payroll);

        return $this->successResponse(
            new PayrollResource($finalized),
            'Payroll finalized successfully'
        );
    }

    public function void(Payroll $payroll, Request $request)
    {
        $this->authorize('update', $payroll);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $voidedPayroll = $this->payrollService->void($payroll, $request->note, Auth::id());

        return $this->successResponse($voidedPayroll, 'Payroll voided successfully');
    }

    public function generateSlip(Payroll $payroll)
    {
        $payroll = $this->payrollService->generateSlip($payroll);

        return response()->json([
            'message' => 'Slip generated successfully',
            'download_url' => Storage::url($payroll->slip_path),
            'generated_at' => $payroll->slip_generated_at,
        ]);
    }

    public function downloadSlip(Payroll $payroll)
    {
        if (! $payroll->slip_path) {
            abort(404, 'Slip not generated.');
        }

        return Storage::download($payroll->slip_path);
    }
}
