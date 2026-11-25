<?php

namespace App\Modules\ResultsFramework\Policies;

use App\Models\User;
use App\Modules\ResultsFramework\Models\Result;
use App\Shared\Policies\OrganizationScopedPolicy;

class ResultPolicy extends OrganizationScopedPolicy
{
    public function update(User $user, Result $result): bool
    {
        return $this->sameOrganization($user, $result->organization_id, 'results_frameworks.edit');
    }

    public function linkIndicator(User $user, Result $result): bool
    {
        return $this->sameOrganization($user, $result->organization_id, 'results_frameworks.edit');
    }
}
