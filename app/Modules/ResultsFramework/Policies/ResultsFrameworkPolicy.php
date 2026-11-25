<?php

namespace App\Modules\ResultsFramework\Policies;

use App\Models\User;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Shared\Policies\OrganizationScopedPolicy;

class ResultsFrameworkPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('results_frameworks.view');
    }

    public function view(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('results_frameworks.create');
    }

    public function update(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.edit');
    }

    public function submit(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.submit');
    }

    public function approve(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.approve');
    }

    public function review(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.review')
            || $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.approve');
    }

    public function manage(User $user, ResultsFramework $framework): bool
    {
        return $this->sameOrganization($user, $framework->organization_id, 'results_frameworks.manage');
    }
}
