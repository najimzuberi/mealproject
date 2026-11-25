<?php

namespace App\Modules\Knowledge\Policies;

use App\Models\User;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Shared\Policies\OrganizationScopedPolicy;

class KnowledgeDocumentPolicy extends OrganizationScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('knowledge.view');
    }

    public function view(User $user, KnowledgeDocument $document): bool
    {
        return $this->sameOrganization($user, $document->organization_id, 'knowledge.view');
    }

    public function create(User $user): bool
    {
        return $user->can('knowledge.create');
    }

    public function update(User $user, KnowledgeDocument $document): bool
    {
        return $this->sameOrganization($user, $document->organization_id, 'knowledge.edit');
    }

    public function download(User $user, KnowledgeDocument $document): bool
    {
        return $this->sameOrganization($user, $document->organization_id, 'knowledge.download')
            || $this->sameOrganization($user, $document->organization_id, 'knowledge.view');
    }

    public function manage(User $user, KnowledgeDocument $document): bool
    {
        return $this->sameOrganization($user, $document->organization_id, 'knowledge.manage');
    }
}
