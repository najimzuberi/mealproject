<?php

namespace App\Modules\AnnualWorkPlan\Resources;

use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnnualWorkPlan */
class AnnualWorkPlanResource extends JsonResource
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
            'strategic_plan_id' => $this->strategic_plan_id,
            'code' => $this->code,
            'title' => $this->title,
            'planning_year' => $this->planning_year,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'currency' => $this->currency,
            'total_budget' => $this->total_budget,
            'description' => $this->description,
            'status' => $this->status,
            'version' => $this->version,
            'parent_awp_id' => $this->parent_awp_id,
            'owner_user_id' => $this->owner_user_id,
            'department_id' => $this->department_id,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'strategic_plan' => $this->whenLoaded('strategicPlan', fn () => [
                'id' => $this->strategicPlan?->id,
                'code' => $this->strategicPlan?->code,
                'title' => $this->strategicPlan?->title,
                'status' => $this->strategicPlan?->status,
            ]),
            'priorities' => AwpPriorityResource::collection($this->whenLoaded('priorities')),
            'plan_milestones' => AwpPlanMilestoneResource::collection($this->whenLoaded('planMilestones')),
            'budget_lines' => AwpBudgetLineResource::collection($this->whenLoaded('budgetLines')),
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
