<?php

namespace App\Modules\DataCollection\Resources;

use App\Modules\DataCollection\Models\ReportingAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportingAssignment */
class ReportingAssignmentResource extends JsonResource
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
            'monitoring_plan_id' => $this->monitoring_plan_id,
            'monitoring_plan_item_id' => $this->monitoring_plan_item_id,
            'reporting_period_id' => $this->reporting_period_id,
            'assigned_to' => $this->assigned_to,
            'supervisor_id' => $this->supervisor_id,
            'due_on' => $this->due_on?->toDateString(),
            'status' => $this->status,
            'monitoring_plan' => $this->whenLoaded('monitoringPlan', fn () => [
                'id' => $this->monitoringPlan?->id,
                'code' => $this->monitoringPlan?->code,
                'title' => $this->monitoringPlan?->title,
            ]),
            'monitoring_plan_item' => $this->whenLoaded('monitoringPlanItem', fn () => [
                'id' => $this->monitoringPlanItem?->id,
                'indicator_id' => $this->monitoringPlanItem?->indicator_id,
                'target_value' => $this->monitoringPlanItem?->target_value,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
