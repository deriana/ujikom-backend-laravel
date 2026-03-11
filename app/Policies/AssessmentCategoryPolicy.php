<?php

namespace App\Policies;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class AssessmentCategoryPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model AssessmentCategory.
 */
class AssessmentCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar kategori penilaian.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('assessment-category.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail kategori penilaian tertentu.
     *
     * @param User $user
     * @param AssessmentCategory $assessmentCategory
     * @return bool
     */
    public function view(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.index');
    }

    /**
     * Menentukan apakah pengguna dapat membuat kategori penilaian baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('assessment-category.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah kategori penilaian.
     *
     * @param User $user
     * @param AssessmentCategory $assessmentCategory
     * @return bool
     */
    public function update(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.edit') && ! $assessmentCategory->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus kategori penilaian.
     *
     * @param User $user
     * @param AssessmentCategory $assessmentCategory
     * @return bool
     */
    public function delete(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.destroy') && ! $assessmentCategory->system_reserve;
    }
}
