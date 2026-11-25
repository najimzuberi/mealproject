<?php

namespace App\Modules\Project\Policies;

use App\Models\User;
use App\Modules\Project\Models\Project;
use App\Shared\Policies\OrganizationScopedPolicy;

class ProjectPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.edit');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.manage');
    }

    public function manage(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.manage');
    }

    public function submit(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.submit');
    }

    public function approve(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.approve');
    }

    public function review(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.approve');
    }

    public function activate(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.manage')
            || $this->sameOrganization($user, $project->organization_id, 'projects.approve');
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.manage')
            || $this->sameOrganization($user, $project->organization_id, 'projects.approve');
    }

    public function manageTeam(User $user, Project $project): bool
    {
        return $this->sameOrganization($user, $project->organization_id, 'projects.edit')
            || $this->sameOrganization($user, $project->organization_id, 'projects.manage');
    }
}
