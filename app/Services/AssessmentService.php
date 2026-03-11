<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentCategory;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    /**
     * Get all assessments with related details.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Assessment::with(['evaluator.user', 'evaluatee.user', 'assessments_details.category']);

        // Apply role-based filtering
        if ($user->hasRole(UserRole::MANAGER->value)) {
            $query->whereHas('evaluatee', function ($q) use ($user) {
                $q->where('manager_id', $user->employee?->id);
            });
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $query->where('evaluatee_id', $user->employee?->id);
        }
        // High-level roles (Admin, Director, etc.) can see all data for the period

        return $query->latest()->get();
    }

    /**
     * Show details of a specific assessment.
     */
    public function show(Assessment $assessment)
    {
        // Cukup sampai .user saja
        return $assessment->load([
            'evaluator.user',
            'evaluatee.user',
            'assessments_details.category',
        ]);
    }

    /**
     * Store a new assessment.
     *
     * * @param array $data ['evaluatee_id', 'period', 'note', 'details' => [...]]
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Evaluator is optional (can be null if created by system/admin without employee profile)
            $evaluator = Auth::user()->employee;
            $evaluatorId = $evaluator ? $evaluator->id : null;

            // Evaluatee is required
            $evaluateeId = Employee::where('nik', $data['evaluatee_nik'])->value('id');
            if (! $evaluateeId) {
                throw new Exception('Evaluatee with the provided NIK not found.');
            }

            $period = Carbon::parse($data['period'])->startOfMonth()->format('Y-m-d');

            $exists = Assessment::where('evaluatee_id', $evaluateeId)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                throw new Exception('An assessment for this employee in the selected period already exists.');
            }

            $assessment = Assessment::create([
                'evaluator_id' => $evaluatorId,
                'evaluatee_id' => $evaluateeId,
                'period' => $period,
                'note' => $data['note'] ?? null,
            ]);

            // Sesuaikan key-nya dengan yang dikirim frontend (assessment_details)
            if (! empty($data['assessment_details'])) {
                $this->syncDetails($assessment, $data['assessment_details']);
            }

            $assessment->notifyCustom(
                title: 'New Assessment Created',
                message: 'A new performance assessment for period '.Carbon::parse($period)->format('M Y').' has been submitted.'
            );

            return $assessment;
        });
    }

    /**
     * Update existing assessment.
     */
   public function update(Assessment $assessment, array $data)
{
    return DB::transaction(function () use ($assessment, $data) {
        // 1. Update data utama (Note)
        $assessment->update([
            'note' => $data['note'] ?? $assessment->note,
        ]);

        // 2. Sync ulang details (Hapus yang lama, buat yang baru)
        // Kita gunakan key 'assessment_details' sesuai yang dikirim frontend
        if (!empty($data['assessment_details'])) {
            $this->syncDetails($assessment, $data['assessment_details']);
        }

        // 3. Send notification
        $assessment->notifyCustom(
            title: 'Assessment Updated',
            message: 'The performance assessment for period '.Carbon::parse($assessment->period)->format('M Y').' has been updated.'
        );

        return $assessment;
    });
}
    /**
     * Sync Assessment Details (Skor)
     */
    protected function syncDetails(Assessment $assessment, array $details)
    {
        // Hapus detail lama jika ada (penting untuk Re-sync saat update)
        $assessment->assessments_details()->delete();

        foreach ($details as $detail) {
            // Cari ID kategori berdasarkan UUID
            $categoryId = AssessmentCategory::where('uuid', $detail['category_uuid'])->value('id');

            if ($categoryId) {
                $assessment->assessments_details()->create([
                    'category_id' => $categoryId,
                    'score' => $detail['score'],
                    'bonus_salary' => $detail['bonus_salary'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Delete assessment.
     */
    public function delete(Assessment $assessment): bool
    {
        return DB::transaction(function () use ($assessment) {
            $assessment->assessments_details()->delete();

            return $assessment->delete();
        });
    }
}
