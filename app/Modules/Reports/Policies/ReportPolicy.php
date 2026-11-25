<?php

namespace App\Modules\Reports\Policies;

use App\Models\User;
use App\Modules\Reports\Models\Report;
use App\Shared\Policies\OrganizationScopedPolicy;

class ReportPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    public function view(User $user, Report $report): bool
    {
        return $this->sameOrganization($user, $report->organization_id, 'reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    public function update(User $user, Report $report): bool
    {
        return $this->sameOrganization($user, $report->organization_id, 'reports.edit');
    }

    public function submit(User $user, Report $report): bool
    {
        return $this->sameOrganization($user, $report->organization_id, 'reports.submit');
    }

    public function approve(User $user, Report $report): bool
    {
        return $this->sameOrganization($user, $report->organization_id, 'reports.approve');
    }

    public function export(User $user, Report $report): bool
    {
        return $this->sameOrganization($user, $report->organization_id, 'reports.export')
            || $this->sameOrganization($user, $report->organization_id, 'reports.view');
    }
}
