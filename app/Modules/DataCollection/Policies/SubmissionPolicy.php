<?php

namespace App\Modules\DataCollection\Policies;

use App\Models\User;
use App\Modules\DataCollection\Models\Submission;
use App\Shared\Policies\OrganizationScopedPolicy;

class SubmissionPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submissions.view');
    }

    public function view(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.create');
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.edit');
    }

    public function submit(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.submit');
    }

    public function verify(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.verify');
    }

    public function validate(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.validate');
    }

    public function approve(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.approve');
    }

    public function manage(User $user, Submission $submission): bool
    {
        return $this->sameOrganization($user, $submission->organization_id, 'submissions.manage');
    }
}
