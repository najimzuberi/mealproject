<?php

namespace App\Modules\Project\Resources;

use App\Modules\Project\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectMilestone */
class ProjectMilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_activity_id' => $this->project_activity_id,
            'title' => $this->title,
            'due_on' => $this->due_on?->toDateString(),
            'status' => $this->status,
        ];
    }
}
