<?php

namespace App\Modules\Project\Resources;

use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\Project\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Programme */
class ProgrammeResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'strategic_alignment' => $this->strategic_alignment,
            'sector_id' => $this->sector_id,
            'department_id' => $this->department_id,
            'programme_manager_id' => $this->programme_manager_id,
            'strategic_plan_id' => $this->strategic_plan_id,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'budget_envelope' => $this->budget_envelope,
            'currency' => $this->currency,
            'donor_name' => $this->donor_name,
            'donor_information' => $this->donor_information,
            'partners' => $this->partners,
            'target_groups' => $this->target_groups,
            'thematic_area' => $this->thematic_area,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'team_members' => $this->whenLoaded('teamMembers', function () {
                return $this->teamMembers->map(fn ($user) => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role_label' => $user->pivot->role_label,
                ])->values();
            }),
            'geographic_unit_ids' => $this->whenLoaded('geographicUnits', fn () => $this->geographicUnits->pluck('id')->values()),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'stakeholders' => StakeholderResource::collection($this->whenLoaded('stakeholders')),
        ];
    }
}
