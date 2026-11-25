<?php

namespace App\Modules\Feedback\Policies;

use App\Models\User;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Shared\Policies\OrganizationScopedPolicy;

class FeedbackCasePolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('feedback.view');
    }

    public function view(User $user, FeedbackCase $case): bool
    {
        if (! $this->sameOrganization($user, $case->organization_id, 'feedback.view')) {
            return false;
        }

        if ($case->isRestricted()) {
            return $user->can('feedback.sensitive') || $user->can('feedback.manage');
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('feedback.create');
    }

    public function update(User $user, FeedbackCase $case): bool
    {
        if (! $this->sameOrganization($user, $case->organization_id, 'feedback.edit')) {
            return false;
        }

        if ($case->isRestricted()) {
            return $user->can('feedback.sensitive') || $user->can('feedback.manage');
        }

        return true;
    }

    public function manage(User $user, FeedbackCase $case): bool
    {
        return $this->sameOrganization($user, $case->organization_id, 'feedback.manage');
    }

    public function manageSensitive(User $user): bool
    {
        return $user->can('feedback.sensitive') || $user->can('feedback.manage');
    }
}
