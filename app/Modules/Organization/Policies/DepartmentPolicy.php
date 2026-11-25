<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Shared\Policies\OrganizationScopedPolicy;

class DepartmentPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $this->sameOrganization($user, $department->organization_id, 'departments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $this->sameOrganization($user, $department->organization_id, 'departments.edit');
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->sameOrganization($user, $department->organization_id, 'departments.manage');
    }
}
