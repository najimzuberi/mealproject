<?php

namespace App\Modules\AnnualWorkPlan\Resources;

use App\Modules\AnnualWorkPlan\Models\AwpPriority;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AwpPriority */
class AwpPriorityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'annual_work_plan_id' => $this->annual_work_plan_id,
            'strategic_objective_id' => $this->strategic_objective_id,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'activities' => AwpActivityResource::collection($this->whenLoaded('activities')),
        ];
    }
}
