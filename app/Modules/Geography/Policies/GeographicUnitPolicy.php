<?php

namespace App\Modules\Geography\Policies;

use App\Models\User;
use App\Modules\Geography\Models\GeographicUnit;
use App\Shared\Policies\OrganizationScopedPolicy;

class GeographicUnitPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('geography.view');
    }

    public function view(User $user, GeographicUnit $unit): bool
    {
        return $this->sameOrganization($user, $unit->organization_id, 'geography.view');
    }

    public function create(User $user): bool
    {
        return $user->can('geography.create');
    }

    public function update(User $user, GeographicUnit $unit): bool
    {
        return $this->sameOrganization($user, $unit->organization_id, 'geography.edit');
    }

    public function delete(User $user, GeographicUnit $unit): bool
    {
        return $this->sameOrganization($user, $unit->organization_id, 'geography.manage');
    }
}
