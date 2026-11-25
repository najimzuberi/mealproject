<?php

namespace App\Modules\MonitoringPlan\Policies;

use App\Models\User;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Shared\Policies\OrganizationScopedPolicy;

class MonitoringPlanPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('monitoring_plans.view');
    }

    public function view(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('monitoring_plans.create');
    }

    public function update(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.edit');
    }

    public function submit(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.submit');
    }

    public function review(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.review');
    }

    public function approve(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.approve');
    }

    public function manage(User $user, MonitoringPlan $plan): bool
    {
        return $this->sameOrganization($user, $plan->organization_id, 'monitoring_plans.manage');
    }
}
