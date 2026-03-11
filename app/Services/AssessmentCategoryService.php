<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\DB;
use Exception;

class AssessmentCategoryService
{
    /**
     * Get all assessment categories with their creator information.
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
     * Store a new assessment category record.
     *
     * @param array $data
     * @return AssessmentCategory
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
     * Update an existing assessment category record.
     *
     * @param AssessmentCategory $assessmentCategory
     * @param array $data
     * @return AssessmentCategory
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
     * Delete an assessment category record.
     *
     * @param AssessmentCategory $assessmentCategory
     * @return bool
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
     * Toggle the active status of an assessment category.
     *
     * @param AssessmentCategory $assessmentCategory
     * @param bool|null $status
     * @return AssessmentCategory
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
