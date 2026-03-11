<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class AssessmentPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Assessment.
 */
class AssessmentPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar penilaian.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('assessment.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail penilaian tertentu.
     *
     * @param User $user
     * @param Assessment $assessment
     * @return bool
     */
    public function view(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data penilaian baru.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('assessment.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data penilaian.
     *
     * @param User $user
     * @param Assessment $assessment
     * @return bool
     */
    public function update(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.edit') && !$assessment->system_reserve;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data penilaian.
     *
     * @param User $user
     * @param Assessment $assessment
     * @return bool
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.destroy') && !$assessment->system_reserve;
    }
}
