<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('assessment.index');
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.show');
    }

    public function create(User $user): bool
    {
        return $user->can('assessment.create');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.edit') && !$assessment->system_reserve;
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.destroy') && !$assessment->system_reserve;
    }
}
