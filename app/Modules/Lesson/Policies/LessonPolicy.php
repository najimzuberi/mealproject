<?php

namespace App\Modules\Lesson\Policies;

use App\Models\User;
use App\Modules\Lesson\Models\Lesson;
use App\Shared\Policies\OrganizationScopedPolicy;

class LessonPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lessons.view');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $this->sameOrganization($user, $lesson->organization_id, 'lessons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lessons.create');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $this->sameOrganization($user, $lesson->organization_id, 'lessons.edit');
    }

    public function submit(User $user, Lesson $lesson): bool
    {
        return $this->sameOrganization($user, $lesson->organization_id, 'lessons.submit');
    }

    public function approve(User $user, Lesson $lesson): bool
    {
        return $this->sameOrganization($user, $lesson->organization_id, 'lessons.approve');
    }

    public function manage(User $user, Lesson $lesson): bool
    {
        return $this->sameOrganization($user, $lesson->organization_id, 'lessons.manage');
    }
}
