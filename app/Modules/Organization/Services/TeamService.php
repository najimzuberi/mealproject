<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\Team;
use App\Shared\Support\OrganizationContext;

class TeamService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Team
    {
        $team = Team::query()->create([
            ...$data,
            'organization_id' => $this->organizationContext->id(),
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'teams', Team::class, $team->id);

        return $team->load(['members', 'leader', 'department', 'office']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Team $team, array $data): Team
    {
        $this->assertOrg($team);
        $team->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'teams', Team::class, $team->id, null, $data);

        return $team->fresh()->load(['members', 'leader', 'department', 'office']);
    }

    /**
     * @param  list<int>  $userIds
     */
    public function syncMembers(Team $team, array $userIds): Team
    {
        $this->assertOrg($team);
        $team->members()->sync($userIds);
        $this->auditService->log(AuditAction::Updated, 'teams', Team::class, $team->id, null, [
            'member_ids' => $userIds,
        ]);

        return $team->fresh()->load(['members', 'leader']);
    }

    public function delete(Team $team): void
    {
        $this->assertOrg($team);
        $team->delete();
        $this->auditService->log(AuditAction::Deleted, 'teams', Team::class, $team->id);
    }

    protected function assertOrg(Team $team): void
    {
        if ($team->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
