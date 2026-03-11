<?php

namespace App\Policies;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('assessment-category.index');
    }

    public function view(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.index');
    }

    public function create(User $user): bool
    {
        return $user->can('assessment-category.create');
    }

    public function update(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.edit') && ! $assessmentCategory->system_reserve;
    }

    public function delete(User $user, AssessmentCategory $assessmentCategory): bool
    {
        return $user->can('assessment-category.destroy') && ! $assessmentCategory->system_reserve;
    }
}
