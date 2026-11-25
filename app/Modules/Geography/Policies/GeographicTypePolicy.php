<?php

namespace App\Modules\Geography\Policies;

use App\Models\User;
use App\Modules\Geography\Models\GeographicType;
use App\Shared\Policies\OrganizationScopedPolicy;

class GeographicTypePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('geography.view');
    }

    public function view(User $user, GeographicType $geographicType): bool
    {
        return $this->sameOrganization($user, $geographicType->organization_id, 'geography.view');
    }

    public function create(User $user): bool
    {
        return $user->can('geography.manage');
    }

    public function update(User $user, GeographicType $geographicType): bool
    {
        return $this->sameOrganization($user, $geographicType->organization_id, 'geography.manage');
    }

    public function delete(User $user, GeographicType $geographicType): bool
    {
        return $this->sameOrganization($user, $geographicType->organization_id, 'geography.manage');
    }
}
