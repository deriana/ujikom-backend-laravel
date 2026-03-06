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

    /** @var int Number of times the job may be attempted. */
    public $tries = 3;

    /** @var int The number of seconds the job can run before timing out. */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Payroll $payroll The payroll model instance to process.
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
            // Eager load necessary relationships to prevent N+1 issues
            $this->payroll->load([
                'employee.user',
                'employee.position.allowances'
            ]);

            // Transform payroll data using the existing API Resource for consistency
            $data = (new PayrollDetailResource($this->payroll))->resolve();

            // Retrieve company general settings (logo, name, etc.)
            $setting = Setting::where('key', 'general')->first();
            $general = $setting?->values ?? [];

            // Render the PDF using the specified blade view
            // This is the most resource-intensive part of the process
            $pdf = Pdf::loadView('pdf.payroll-slip', [
                'data' => $data,
                'company' => $general,
            ]);

            // Define the storage path using the payroll UUID
            $fileName = "slips/{$this->payroll->uuid}.pdf";

            // Save the generated PDF content to the storage disk
            Storage::put($fileName, $pdf->output());

            // Update the payroll record with the file path and generation timestamp
            $this->payroll->update([
                'slip_path' => $fileName,
                'slip_generated_at' => now(),
            ]);

            // Log::info("Slip PDF generated successfully for Payroll: {$this->payroll->uuid}");

        } catch (\Exception $e) {
            // Log::error("Failed to generate slip for Payroll {$this->payroll->uuid}: " . $e->getMessage());
            throw $e; // Rethrow to allow Laravel's queue worker to handle retries
        }
    }
}
