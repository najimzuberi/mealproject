<?php

namespace App\Modules\AnnualWorkPlan\Resources;

use App\Modules\AnnualWorkPlan\Models\AwpPlanMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AwpPlanMilestone */
class AwpPlanMilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'annual_work_plan_id' => $this->annual_work_plan_id,
            'strategic_objective_id' => $this->strategic_objective_id,
            'department_id' => $this->department_id,
            'team_id' => $this->team_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'quarter' => $this->quarter,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'expected_result' => $this->expected_result,
            'status' => $this->status,
        ];
    }
}
