<?php

namespace App\Modules\DataCollection\Policies;

use App\Models\User;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Shared\Policies\OrganizationScopedPolicy;

class ReportingAssignmentPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submissions.view');
    }

    public function view(User $user, ReportingAssignment $assignment): bool
    {
        return $this->sameOrganization($user, $assignment->organization_id, 'submissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.manage') || $user->can('monitoring_plans.manage');
    }
}
