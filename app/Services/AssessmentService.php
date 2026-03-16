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

/**
 * Class AssessmentService
 *
 * Menangani logika bisnis untuk penilaian kinerja (performance assessment) karyawan,
 * termasuk perhitungan skor per kategori, manajemen periode, dan integrasi notifikasi.
 */
 class AssessmentService
{
    /**
     * Mengambil daftar semua penilaian dengan filter berdasarkan peran pengguna.
     *
     * @return \Illuminate\Database\Eloquent\Collection
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
     * Menampilkan detail lengkap dari satu penilaian tertentu.
     *
     * @param Assessment $assessment Objek penilaian.
     * @return Assessment
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
     * Menyimpan data penilaian kinerja baru ke dalam database.
     *
     * @param array $data Data penilaian (evaluatee_nik, period, note, assessment_details).
     * @return Assessment
     * @throws Exception Jika penilaian untuk karyawan pada periode tersebut sudah ada atau NIK tidak valid.
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
     * Memperbarui data penilaian yang sudah ada.
     *
     * @param Assessment $assessment Objek penilaian yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return Assessment
     */
    public function update(Assessment $assessment, array $data)
    {
        return DB::transaction(function () use ($assessment, $data) {
            $updateData = [
                'note' => $data['note'] ?? $assessment->note,
            ];

            if (isset($data['period'])) {
                $updateData['period'] = Carbon::parse($data['period'])->startOfMonth()->format('Y-m-d');
            }

            $assessment->update($updateData);

            if (!empty($data['assessment_details'])) {
                $this->syncDetails($assessment, $data['assessment_details']);
            }

            $assessment->notifyCustom(
                title: 'Assessment Updated',
                message: 'The performance assessment for period '.Carbon::parse($assessment->period)->format('M Y').' has been updated.'
            );

            return $assessment;
        });
    }

    /**
     * Menyinkronkan detail skor penilaian per kategori.
     *
     * @param Assessment $assessment Objek penilaian.
     * @param array $details Array berisi category_uuid, score, dan bonus_salary.
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
     * Menghapus data penilaian beserta detail skornya.
     *
     * @param Assessment $assessment Objek penilaian yang akan dihapus.
     * @return bool
     */
    public function delete(Assessment $assessment): bool
    {
        return DB::transaction(function () use ($assessment) {
            $assessment->assessments_details()->delete();

            return $assessment->delete();
        });
    }
}
