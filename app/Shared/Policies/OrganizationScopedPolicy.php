<?php

namespace App\Shared\Policies;

use App\Models\User;
use App\Shared\Support\OrganizationContext;

abstract class OrganizationScopedPolicy
{
    protected function sameOrganization(User $user, int $organizationId, string $permission): bool
    {
        $contextId = app(OrganizationContext::class)->id();

        if ($contextId === null || $contextId !== $organizationId) {
            return false;
        }

        return $user->can($permission);
    }
}
