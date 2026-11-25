<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Organization;
use App\Shared\Policies\OrganizationScopedPolicy;

class OrganizationPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->sameOrganization($user, $organization->id, 'organization.view')
            || $user->organizations()->where('organizations.id', $organization->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->sameOrganization($user, $organization->id, 'organization.edit')
            || $user->can('organization.manage');
    }

    public function manage(User $user, Organization $organization): bool
    {
        return $this->sameOrganization($user, $organization->id, 'organization.manage');
    }

    public function platformList(User $user): bool
    {
        foreach ($user->organizations as $org) {
            setPermissionsTeamId($org->id);
            if ($user->hasRole('system_administrator')) {
                return true;
            }
        }

        return false;
    }
}
