<?php

namespace App\Modules\Dqa\Policies;

use App\Models\User;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Shared\Policies\OrganizationScopedPolicy;

class DqaAssessmentPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dqa.view');
    }

    public function view(User $user, DqaAssessment $assessment): bool
    {
        return $this->sameOrganization($user, $assessment->organization_id, 'dqa.view');
    }

    public function create(User $user): bool
    {
        return $user->can('dqa.create');
    }

    public function update(User $user, DqaAssessment $assessment): bool
    {
        return $this->sameOrganization($user, $assessment->organization_id, 'dqa.edit');
    }

    public function manage(User $user, DqaAssessment $assessment): bool
    {
        return $this->sameOrganization($user, $assessment->organization_id, 'dqa.manage');
    }
}
