<?php

namespace App\Modules\ReportingCalendar\Policies;

use App\Models\User;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Shared\Policies\OrganizationScopedPolicy;

class ReportingCalendarPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reporting_calendars.view');
    }

    public function view(User $user, ReportingCalendar $calendar): bool
    {
        return $this->sameOrganization($user, $calendar->organization_id, 'reporting_calendars.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reporting_calendars.create');
    }

    public function update(User $user, ReportingCalendar $calendar): bool
    {
        return $this->sameOrganization($user, $calendar->organization_id, 'reporting_calendars.edit');
    }

    public function manage(User $user, ReportingCalendar $calendar): bool
    {
        return $this->sameOrganization($user, $calendar->organization_id, 'reporting_calendars.manage');
    }
}
