<?php

namespace App\Modules\StrategicPlanning\Policies;

use App\Models\User;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Policies\OrganizationScopedPolicy;

class StrategicPlanPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('strategic_plans.view');
    }

    public function view(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('strategic_plans.create');
    }

    public function update(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.edit');
    }

    public function delete(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.manage');
    }

    public function submit(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.submit');
    }

    public function approve(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.approve');
    }

    public function review(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.review')
            || $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.approve');
    }

    public function activate(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.manage')
            || $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.approve');
    }

    public function archive(User $user, StrategicPlan $strategicPlan): bool
    {
        return $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.manage')
            || $this->sameOrganization($user, $strategicPlan->organization_id, 'strategic_plans.approve');
    }
}
