<?php

namespace App\Jobs;

use App\Models\Payroll;
use App\Models\Setting;
use App\Http\Resources\PayrollDetailResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Job to generate a PDF payroll slip for a specific payroll record.
 * This job is queued to handle heavy PDF rendering in the background.
 */
class GeneratePayrollSlipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; /**< Jumlah maksimal percobaan ulang jika job gagal */

    public $timeout = 120; /**< Batas waktu eksekusi job dalam detik sebelum dianggap timeout */

    /**
     * Membuat instance job baru.
     *
     * @param Payroll $payroll Instance model payroll yang akan diproses.
     */
    public function __construct(
        protected Payroll $payroll
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            // 1. Tentukan string periode (Y-m)
            $periodString = $this->payroll->period_start->format('Y-m');

            // 2. Eager load relasi yang benar (Pakai assessmentsAsEvaluatee)
            $this->payroll->load([
                'employee.user',
                'employee.position.allowances',
                // UBAH DI SINI: Samakan dengan yang ada di PayrollService
                'employee.assessmentsAsEvaluatee' => function ($query) use ($periodString) {
                    $query->where('period', 'like', $periodString . '%')
                          ->with('assessments_details');
                }
            ]);

            // 3. Transform data (Resource akan otomatis membaca relasi yang sudah di-load)
            $data = (new PayrollDetailResource($this->payroll))->resolve();

            // Sisanya tetap sama...
            $setting = Setting::where('key', 'general')->first();
            $general = $setting?->values ?? [];

            // 5. Render PDF
            $pdf = Pdf::loadView('pdf.payroll-slip', [
                'data' => $data,
                'company' => $general,
                'setting' => $setting
            ]);

            $fileName = "slips/{$this->payroll->uuid}.pdf";

            // 6. Simpan ke Storage
            Storage::put($fileName, $pdf->output());

            // 7. Update record payroll
            $this->payroll->update([
                'slip_path' => $fileName,
                'slip_generated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate payroll slip for UUID: {$this->payroll->uuid}. Error: {$e->getMessage()}");

            throw $e;
        }
    }
}
