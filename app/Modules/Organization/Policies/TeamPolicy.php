<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Team;
use App\Shared\Policies\OrganizationScopedPolicy;

class TeamPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teams.view');
    }

    public function view(User $user, Team $team): bool
    {
        return $this->sameOrganization($user, $team->organization_id, 'teams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('teams.create');
    }

    public function update(User $user, Team $team): bool
    {
        return $this->sameOrganization($user, $team->organization_id, 'teams.edit');
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->sameOrganization($user, $team->organization_id, 'teams.manage');
    }
}
