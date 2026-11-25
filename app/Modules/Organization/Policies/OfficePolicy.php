<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Office;
use App\Shared\Policies\OrganizationScopedPolicy;

class OfficePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('offices.view');
    }

    public function view(User $user, Office $office): bool
    {
        return $this->sameOrganization($user, $office->organization_id, 'offices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('offices.create');
    }

    public function update(User $user, Office $office): bool
    {
        return $this->sameOrganization($user, $office->organization_id, 'offices.edit');
    }

    public function delete(User $user, Office $office): bool
    {
        return $this->sameOrganization($user, $office->organization_id, 'offices.manage');
    }
}
