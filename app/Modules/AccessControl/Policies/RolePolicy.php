<?php

namespace App\Modules\AccessControl\Policies;

use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Shared\Policies\OrganizationScopedPolicy;

class RolePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->sameOrganization($user, (int) $role->organization_id, 'roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->sameOrganization($user, (int) $role->organization_id, 'roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->sameOrganization($user, (int) $role->organization_id, 'roles.manage');
    }
}
