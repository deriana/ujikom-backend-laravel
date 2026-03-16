<?php

namespace App\Jobs;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateEmployeeProfilePdf implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->load([
            'employee.position.allowances',
            'employee.team.division',
            'employee.manager.user',
            'roles'
        ]);

        // 2. Render View ke PDF
        $pdf = Pdf::loadView('pdfs.employee-profile', ['user' => $this->user]);

        // 3. Simpan ke Storage
        $fileName = 'profiles/profile-' . $this->user->uuid . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
    }
}
