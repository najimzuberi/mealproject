<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Facility;
use App\Shared\Policies\OrganizationScopedPolicy;

class FacilityPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('facilities.view');
    }

    public function view(User $user, Facility $facility): bool
    {
        return $this->sameOrganization($user, $facility->organization_id, 'facilities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('facilities.create');
    }

    public function update(User $user, Facility $facility): bool
    {
        return $this->sameOrganization($user, $facility->organization_id, 'facilities.edit');
    }

    public function delete(User $user, Facility $facility): bool
    {
        return $this->sameOrganization($user, $facility->organization_id, 'facilities.manage');
    }
}
