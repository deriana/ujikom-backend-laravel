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

class GeneratePayrollSlipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // public $afterCommit = true;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        protected Payroll $payroll
    ) {}

    public function handle(): void
    {
        try {
            // Eager loading relasi yang dibutuhkan
            $this->payroll->load([
                'employee.user',
                'employee.position.allowances'
            ]);

            // Transformasi data menggunakan Resource
            $data = (new PayrollDetailResource($this->payroll))->resolve();

            // Ambil setting perusahaan
            $setting = Setting::where('key', 'general')->first();
            $general = $setting?->values ?? [];

            // Render PDF (Proses paling berat)
            $pdf = Pdf::loadView('pdf.payroll-slip', [
                'data' => $data,
                'company' => $general,
            ]);

            $fileName = "slips/{$this->payroll->uuid}.pdf";

            // Simpan ke storage
            Storage::put($fileName, $pdf->output());

            // Update record payroll
            $this->payroll->update([
                'slip_path' => $fileName,
                'slip_generated_at' => now(),
            ]);

            // Log::info("Slip PDF generated successfully for Payroll: {$this->payroll->uuid}");

        } catch (\Exception $e) {
            // Log::error("Failed to generate slip for Payroll {$this->payroll->uuid}: " . $e->getMessage());
            throw $e; // Throw agar Laravel tahu job ini gagal dan perlu dicoba lagi
        }
    }
}
