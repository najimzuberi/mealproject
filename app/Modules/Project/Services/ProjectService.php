<?php

namespace App\Modules\Project\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\ProjectStatus;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Documents\Models\Document;
use App\Modules\Project\Models\Project;
use App\Modules\Project\Models\ProjectActivity;
use App\Modules\Project\Models\ProjectAssumption;
use App\Modules\Project\Models\ProjectMilestone;
use App\Modules\Project\Models\ProjectRisk;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        $this->validatePlanLinks($data);

        return DB::transaction(function () use ($data) {
            $nested = $this->extractNested($data);

            $project = Project::query()->create([
                ...$data,
                'organization_id' => $this->organizationContext->id(),
                'status' => ProjectStatus::Draft->value,
                'created_by' => Auth::id(),
            ]);

            if ($nested !== null) {
                $this->syncNested($project, $nested);
            }

            $this->auditService->log(AuditAction::Created, 'projects', Project::class, $project->id);

            return $this->loadFullStructure($project);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $this->assertOrg($project);
        $this->assertEditable($project);

        if ($this->hasPlanFields($data)) {
            $merged = array_merge($project->only([
                'strategic_plan_id',
                'strategic_objective_id',
                'annual_work_plan_id',
            ]), $data);
            $this->validatePlanLinks($merged);
        }

        return DB::transaction(function () use ($project, $data) {
            $nested = $this->extractNested($data);

            if ($data !== []) {
                $project->fill($data)->save();
            }

            if ($nested !== null) {
                $this->clearNested($project);
                $this->syncNested($project, $nested);
            }

            $this->auditService->log(AuditAction::Updated, 'projects', Project::class, $project->id);

            return $this->loadFullStructure($project->fresh());
        });
    }

    public function delete(Project $project): void
    {
        $this->assertOrg($project);

        if ($project->status !== ProjectStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Only draft projects can be deleted.'],
            ]);
        }

        $project->delete();
        $this->auditService->log(AuditAction::Deleted, 'projects', Project::class, $project->id);
    }

    public function submit(Project $project): Project
    {
        $this->assertOrg($project);

        return $this->loadFullStructure($this->workflowService->transition(
            $project,
            ProjectStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [ProjectStatus::Draft->value, ProjectStatus::Returned->value],
            auditModule: 'projects',
        ));
    }

    public function returnForCorrection(Project $project, string $comment): Project
    {
        $this->assertOrg($project);

        return $this->loadFullStructure($this->workflowService->transition(
            $project,
            ProjectStatus::Returned->value,
            ApprovalAction::Returned,
            [ProjectStatus::UnderReview->value],
            $comment,
            auditModule: 'projects',
            requireComment: true,
        ));
    }

    public function approve(Project $project, ?string $comment = null): Project
    {
        $this->assertOrg($project);

        return $this->loadFullStructure($this->workflowService->transition(
            $project,
            ProjectStatus::Approved->value,
            ApprovalAction::Approved,
            [ProjectStatus::UnderReview->value],
            $comment,
            auditModule: 'projects',
            setApprovedMeta: true,
        ));
    }

    public function activate(Project $project): Project
    {
        $this->assertOrg($project);

        $status = $project->status instanceof ProjectStatus
            ? $project->status
            : ProjectStatus::from((string) $project->status);

        // Backward-compatible: draft may activate directly; preferred path is approved → active.
        $from = match ($status) {
            ProjectStatus::Draft, ProjectStatus::Approved => [$status->value],
            default => [],
        };

        if ($from === []) {
            throw ValidationException::withMessages([
                'status' => ['Only draft or approved projects can be activated.'],
            ]);
        }

        return $this->loadFullStructure($this->workflowService->transition(
            $project,
            ProjectStatus::Active->value,
            ApprovalAction::Activated,
            $from,
            auditModule: 'projects',
        ));
    }

    public function archive(Project $project): Project
    {
        $this->assertOrg($project);

        return $this->loadFullStructure($this->workflowService->transition(
            $project,
            ProjectStatus::Archived->value,
            ApprovalAction::Archived,
            [
                ProjectStatus::Active->value,
                ProjectStatus::OnHold->value,
                ProjectStatus::Completed->value,
                ProjectStatus::Approved->value,
            ],
            auditModule: 'projects',
        ));
    }

    public function storeDocument(Project $project, UploadedFile $file, ?string $title = null): Document
    {
        $this->assertOrg($project);

        $path = $file->store("projects/{$project->id}", 'public');

        $document = $project->documents()->create([
            'organization_id' => $project->organization_id,
            'title' => $title ?: $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditService->log(AuditAction::Created, 'projects', Document::class, $document->id);

        return $document;
    }

    public function deleteDocument(Project $project, Document $document): void
    {
        $this->assertOrg($project);

        if ($document->documentable_type !== Project::class || (int) $document->documentable_id !== (int) $project->id) {
            abort(404);
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    /**
     * @param  list<array{user_id: int, role_label?: string|null}>  $members
     */
    public function syncTeam(Project $project, array $members): Project
    {
        $this->assertOrg($project);

        $sync = [];
        foreach ($members as $member) {
            $sync[(int) $member['user_id']] = ['role_label' => $member['role_label'] ?? null];
        }

        $project->teamMembers()->sync($sync);

        $this->auditService->log(AuditAction::Updated, 'projects', Project::class, $project->id, null, [
            'team_synced' => array_keys($sync),
        ]);

        return $this->loadFullStructure($project->fresh());
    }

    /**
     * @param  list<int>  $geographicUnitIds
     */
    public function syncGeographicUnits(Project $project, array $geographicUnitIds): Project
    {
        $this->assertOrg($project);
        $project->geographicUnits()->sync($geographicUnitIds);

        $this->auditService->log(AuditAction::Updated, 'projects', Project::class, $project->id, null, [
            'geographic_unit_ids' => $geographicUnitIds,
        ]);

        return $this->loadFullStructure($project->fresh());
    }

    public function loadFullStructure(Project $project): Project
    {
        return $project->load([
            'activities.milestones',
            'milestones',
            'assumptions',
            'risks',
            'teamMembers',
            'geographicUnits',
            'programme',
            'documents',
            'stakeholders',
            'approvalHistories',
            'projectManager',
            'sector',
            'department',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validatePlanLinks(array $data): void
    {
        $organizationId = $this->organizationContext->id();

        if (! empty($data['strategic_plan_id'])) {
            $planId = (int) $data['strategic_plan_id'];
            $planValid = StrategicPlan::query()
                ->where('id', $planId)
                ->where('organization_id', $organizationId)
                ->exists();

            if (! $planValid) {
                throw ValidationException::withMessages([
                    'strategic_plan_id' => ['Strategic plan must belong to this organization.'],
                ]);
            }

            if (! empty($data['strategic_objective_id'])) {
                $objectiveValid = StrategicObjective::query()
                    ->where('id', (int) $data['strategic_objective_id'])
                    ->where('organization_id', $organizationId)
                    ->whereHas('strategicGoal', fn ($query) => $query->where('strategic_plan_id', $planId))
                    ->exists();

                if (! $objectiveValid) {
                    throw ValidationException::withMessages([
                        'strategic_objective_id' => ['Strategic objective must belong to the selected strategic plan.'],
                    ]);
                }
            }
        }

        if (! empty($data['annual_work_plan_id'])) {
            $awp = AnnualWorkPlan::query()
                ->where('id', (int) $data['annual_work_plan_id'])
                ->where('organization_id', $organizationId)
                ->first();

            if (! $awp) {
                throw ValidationException::withMessages([
                    'annual_work_plan_id' => ['Annual work plan must belong to this organization.'],
                ]);
            }

            if (! empty($data['strategic_plan_id']) && (int) $data['strategic_plan_id'] !== (int) $awp->strategic_plan_id) {
                throw ValidationException::withMessages([
                    'annual_work_plan_id' => ['Annual work plan must align with the selected strategic plan.'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{activities: list<array<string, mixed>>, milestones: list<array<string, mixed>>, assumptions: list<array<string, mixed>>, risks: list<array<string, mixed>>}|null
     */
    protected function extractNested(array &$data): ?array
    {
        $hasNested = isset($data['activities']) || isset($data['milestones']) || isset($data['assumptions']) || isset($data['risks']);

        if (! $hasNested) {
            return null;
        }

        $nested = [
            'activities' => $data['activities'] ?? [],
            'milestones' => $data['milestones'] ?? [],
            'assumptions' => $data['assumptions'] ?? [],
            'risks' => $data['risks'] ?? [],
        ];

        unset($data['activities'], $data['milestones'], $data['assumptions'], $data['risks']);

        return $nested;
    }

    /**
     * @param  array{activities: list<array<string, mixed>>, milestones: list<array<string, mixed>>, assumptions: list<array<string, mixed>>, risks: list<array<string, mixed>>}  $nested
     */
    protected function syncNested(Project $project, array $nested): void
    {
        $organizationId = $project->organization_id;
        $activityMap = [];

        foreach ($nested['activities'] as $activityData) {
            $activity = ProjectActivity::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $project->id,
                'title' => $activityData['title'],
                'description' => $activityData['description'] ?? null,
                'responsible_user_id' => $activityData['responsible_user_id'] ?? null,
                'starts_on' => $activityData['starts_on'],
                'ends_on' => $activityData['ends_on'],
                'status' => $activityData['status'] ?? 'planned',
            ]);

            if (isset($activityData['index'])) {
                $activityMap[$activityData['index']] = $activity->id;
            }

            foreach ($activityData['milestones'] ?? [] as $milestoneData) {
                ProjectMilestone::query()->create([
                    'organization_id' => $organizationId,
                    'project_id' => $project->id,
                    'project_activity_id' => $activity->id,
                    'title' => $milestoneData['title'],
                    'due_on' => $milestoneData['due_on'] ?? null,
                    'status' => $milestoneData['status'] ?? 'pending',
                ]);
            }
        }

        foreach ($nested['milestones'] as $milestoneData) {
            $activityId = $milestoneData['project_activity_id']
                ?? (isset($milestoneData['activity_index']) ? ($activityMap[$milestoneData['activity_index']] ?? null) : null);

            ProjectMilestone::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $project->id,
                'project_activity_id' => $activityId,
                'title' => $milestoneData['title'],
                'due_on' => $milestoneData['due_on'] ?? null,
                'status' => $milestoneData['status'] ?? 'pending',
            ]);
        }

        foreach ($nested['assumptions'] as $assumptionData) {
            ProjectAssumption::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $project->id,
                'description' => $assumptionData['description'],
            ]);
        }

        foreach ($nested['risks'] as $riskData) {
            ProjectRisk::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $project->id,
                'description' => $riskData['description'],
                'likelihood' => $riskData['likelihood'] ?? null,
                'impact' => $riskData['impact'] ?? null,
                'mitigation' => $riskData['mitigation'] ?? null,
                'status' => $riskData['status'] ?? 'open',
            ]);
        }
    }

    protected function clearNested(Project $project): void
    {
        $project->activities()->each(function (ProjectActivity $activity): void {
            $activity->milestones()->delete();
            $activity->delete();
        });
        $project->milestones()->whereNull('project_activity_id')->delete();
        $project->assumptions()->delete();
        $project->risks()->delete();
    }

    protected function assertEditable(Project $project): void
    {
        $this->workflowService->assertEditable($project, [
            ProjectStatus::Draft->value,
            ProjectStatus::Returned->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasPlanFields(array $data): bool
    {
        return array_intersect(array_keys($data), [
            'strategic_plan_id',
            'strategic_objective_id',
            'annual_work_plan_id',
        ]) !== [];
    }

    protected function assertOrg(Project $project): void
    {
        if ($project->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
