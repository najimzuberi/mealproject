<?php

namespace App\Modules\Audit\Policies;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Shared\Policies\OrganizationScopedPolicy;

class AuditLogPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        if ($log->organization_id === null) {
            return $user->can('audit.view');
        }

        return $this->sameOrganization($user, (int) $log->organization_id, 'audit.view');
    }
}
