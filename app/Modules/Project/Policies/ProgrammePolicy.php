<?php

namespace App\Modules\Project\Policies;

use App\Models\User;
use App\Modules\Project\Models\Programme;
use App\Shared\Policies\OrganizationScopedPolicy;

class ProgrammePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Programme $programme): bool
    {
        return $this->sameOrganization($user, $programme->organization_id, 'projects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Programme $programme): bool
    {
        return $this->sameOrganization($user, $programme->organization_id, 'projects.edit');
    }

    public function delete(User $user, Programme $programme): bool
    {
        return $this->sameOrganization($user, $programme->organization_id, 'projects.manage');
    }
}
