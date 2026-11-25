<?php

namespace App\Modules\Indicator\Policies;

use App\Models\User;
use App\Modules\Indicator\Models\Indicator;
use App\Shared\Policies\OrganizationScopedPolicy;

class IndicatorPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('indicators.view');
    }

    public function view(User $user, Indicator $indicator): bool
    {
        return $this->sameOrganization($user, $indicator->organization_id, 'indicators.view');
    }

    public function create(User $user): bool
    {
        return $user->can('indicators.create');
    }

    public function update(User $user, Indicator $indicator): bool
    {
        return $this->sameOrganization($user, $indicator->organization_id, 'indicators.edit');
    }

    public function submit(User $user, Indicator $indicator): bool
    {
        return $this->sameOrganization($user, $indicator->organization_id, 'indicators.submit');
    }

    public function approve(User $user, Indicator $indicator): bool
    {
        return $this->sameOrganization($user, $indicator->organization_id, 'indicators.approve');
    }

    public function manage(User $user, Indicator $indicator): bool
    {
        return $this->sameOrganization($user, $indicator->organization_id, 'indicators.manage');
    }
}
