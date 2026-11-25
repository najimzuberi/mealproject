<?php

namespace App\Modules\AnnualWorkPlan\Policies;

use App\Models\User;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Shared\Policies\OrganizationScopedPolicy;

class AnnualWorkPlanPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('annual_work_plans.view');
    }

    public function view(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('annual_work_plans.create');
    }

    public function update(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.edit');
    }

    public function submit(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.submit');
    }

    public function approve(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.approve');
    }

    public function review(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.review')
            || $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.approve');
    }

    public function activate(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.manage')
            || $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.approve');
    }

    public function archive(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.manage')
            || $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.approve');
    }

    public function delete(User $user, AnnualWorkPlan $awp): bool
    {
        return $this->sameOrganization($user, $awp->organization_id, 'annual_work_plans.manage');
    }
}
