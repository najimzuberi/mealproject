<?php

namespace App\Modules\Project\Resources;

use App\Modules\Project\Models\ProjectActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectActivity */
class ProjectActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'responsible_user_id' => $this->responsible_user_id,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $this->status,
            'milestones' => ProjectMilestoneResource::collection($this->whenLoaded('milestones')),
        ];
    }
}
