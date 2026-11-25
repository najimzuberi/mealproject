<?php

namespace App\Modules\Project\Resources;

use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'programme_id' => $this->programme_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'background' => $this->background,
            'problem_statement' => $this->problem_statement,
            'target_groups' => $this->target_groups,
            'strategic_plan_id' => $this->strategic_plan_id,
            'strategic_objective_id' => $this->strategic_objective_id,
            'annual_work_plan_id' => $this->annual_work_plan_id,
            'sector_id' => $this->sector_id,
            'department_id' => $this->department_id,
            'project_manager_id' => $this->project_manager_id,
            'funding_source' => $this->funding_source,
            'donor_name' => $this->donor_name,
            'donor_information' => $this->donor_information,
            'budget' => $this->budget,
            'currency' => $this->currency,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'programme' => new ProgrammeResource($this->whenLoaded('programme')),
            'activities' => ProjectActivityResource::collection($this->whenLoaded('activities')),
            'milestones' => ProjectMilestoneResource::collection($this->whenLoaded('milestones')),
            'assumptions' => ProjectAssumptionResource::collection($this->whenLoaded('assumptions')),
            'risks' => ProjectRiskResource::collection($this->whenLoaded('risks')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'stakeholders' => StakeholderResource::collection($this->whenLoaded('stakeholders')),
            'team_members' => $this->whenLoaded('teamMembers', function () {
                return $this->teamMembers->map(fn ($user) => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role_label' => $user->pivot->role_label,
                ])->values();
            }),
            'geographic_unit_ids' => $this->whenLoaded('geographicUnits', fn () => $this->geographicUnits->pluck('id')->values()),
            'approval_history' => $this->whenLoaded('approvalHistories', function () {
                return $this->approvalHistories->map(fn ($history) => [
                    'id' => $history->id,
                    'action' => $history->action,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'comment' => $history->comment,
                    'user_id' => $history->user_id,
                    'created_at' => $history->created_at,
                ]);
            }),
        ];
    }
}
