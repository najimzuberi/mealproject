<?php

namespace App\Modules\Audit\Policies;

use App\Models\User;

class LoginLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }
}
