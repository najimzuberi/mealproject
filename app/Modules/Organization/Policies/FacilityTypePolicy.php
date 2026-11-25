<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\FacilityType;
use App\Shared\Policies\OrganizationScopedPolicy;

class FacilityTypePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('facilities.view');
    }

    public function view(User $user, FacilityType $facilityType): bool
    {
        return $this->sameOrganization($user, $facilityType->organization_id, 'facilities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('facilities.manage');
    }

    public function update(User $user, FacilityType $facilityType): bool
    {
        return $this->sameOrganization($user, $facilityType->organization_id, 'facilities.manage');
    }

    public function delete(User $user, FacilityType $facilityType): bool
    {
        return $this->sameOrganization($user, $facilityType->organization_id, 'facilities.manage');
    }
}
