<?php

namespace App\Modules\Evaluation\Policies;

use App\Models\User;
use App\Modules\Evaluation\Models\Evaluation;
use App\Shared\Policies\OrganizationScopedPolicy;

class EvaluationPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('evaluations.view');
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        return $this->sameOrganization($user, $evaluation->organization_id, 'evaluations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('evaluations.create');
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $this->sameOrganization($user, $evaluation->organization_id, 'evaluations.edit');
    }

    public function submit(User $user, Evaluation $evaluation): bool
    {
        return $this->sameOrganization($user, $evaluation->organization_id, 'evaluations.submit');
    }

    public function approve(User $user, Evaluation $evaluation): bool
    {
        return $this->sameOrganization($user, $evaluation->organization_id, 'evaluations.approve');
    }

    public function manage(User $user, Evaluation $evaluation): bool
    {
        return $this->sameOrganization($user, $evaluation->organization_id, 'evaluations.manage');
    }
}
