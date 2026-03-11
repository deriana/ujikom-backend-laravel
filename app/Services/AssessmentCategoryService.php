<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Class AssessmentCategoryService
 *
 * Menangani logika bisnis untuk kategori penilaian (assessment category),
 * termasuk operasi CRUD dan pengelolaan status aktif.
 */
class AssessmentCategoryService
{
    /**
     * Mengambil semua kategori penilaian beserta informasi pembuatnya.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve all assessment categories with eager loaded creator relationship
        return AssessmentCategory::with(['creator'])
            ->latest()
            ->get();
    }

    /**
     * Menyimpan data kategori penilaian baru ke dalam database.
     *
     * @param array $data Data kategori (name, description, is_active).
     * @return AssessmentCategory Objek kategori yang berhasil dibuat.
     */
    public function store(array $data): AssessmentCategory
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the assessment category record in the database
            return AssessmentCategory::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Memperbarui data kategori penilaian yang sudah ada.
     *
     * @param AssessmentCategory $assessmentCategory Objek kategori yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return AssessmentCategory Objek kategori setelah diperbarui.
     */
    public function update(AssessmentCategory $assessmentCategory, array $data): AssessmentCategory
    {
        return DB::transaction(function () use ($assessmentCategory, $data) {
            // 1. Update the assessment category attributes with provided data or keep existing values
            $assessmentCategory->update([
                'name' => $data['name'] ?? $assessmentCategory->name,
                'description' => $data['description'] ?? $assessmentCategory->description,
                'is_active' => $data['is_active'] ?? $assessmentCategory->is_active,
            ]);

            return $assessmentCategory;
        });
    }

    /**
     * Menghapus data kategori penilaian dari database.
     *
     * @param AssessmentCategory $assessmentCategory Objek kategori yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     * @throws Exception Jika kategori sudah digunakan dalam detail penilaian.
     */
    public function delete(AssessmentCategory $assessmentCategory): bool
    {
        return DB::transaction(function () use ($assessmentCategory) {
            // Check if category is already used in assessments
            if ($assessmentCategory->assessmentsDetails()->exists()) {
                throw new Exception('Cannot delete category that is already used in assessments.');
            }

            // 1. Perform the deletion of the assessment category record
            return (bool) $assessmentCategory->delete();
        });
    }

    /**
     * Mengubah status aktif/nonaktif dari kategori penilaian.
     *
     * @param AssessmentCategory $assessmentCategory Objek kategori.
     * @param bool|null $status Status baru (opsional, jika null maka akan di-toggle).
     * @return AssessmentCategory Objek kategori dengan status terbaru.
     */
    public function toggleStatus(AssessmentCategory $assessmentCategory, ?bool $status = null): AssessmentCategory
    {
        return DB::transaction(function () use ($assessmentCategory, $status) {
            $newStatus = $status ?? !$assessmentCategory->is_active;

            $assessmentCategory->update([
                'is_active' => $newStatus,
            ]);

            return $assessmentCategory;
        });
    }
}
