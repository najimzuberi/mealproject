<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Shared\Policies\OrganizationScopedPolicy;
use App\Shared\Support\OrganizationContext;

class UserPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view')
            && $model->organizations()->where('organizations.id', app(OrganizationContext::class)->id())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.edit')
            && $model->organizations()->where('organizations.id', app(OrganizationContext::class)->id())->exists();
    }

    public function manage(User $user): bool
    {
        return $user->can('users.manage');
    }
}
