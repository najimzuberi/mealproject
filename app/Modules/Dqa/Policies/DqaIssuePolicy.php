<?php

namespace App\Modules\Dqa\Policies;

use App\Models\User;
use App\Modules\Dqa\Models\DqaIssue;
use App\Shared\Policies\OrganizationScopedPolicy;

class DqaIssuePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dqa.view');
    }

    public function view(User $user, DqaIssue $issue): bool
    {
        return $this->sameOrganization($user, $issue->organization_id, 'dqa.view');
    }

    public function create(User $user): bool
    {
        return $user->can('dqa.create');
    }

    public function update(User $user, DqaIssue $issue): bool
    {
        return $this->sameOrganization($user, $issue->organization_id, 'dqa.edit');
    }
}
