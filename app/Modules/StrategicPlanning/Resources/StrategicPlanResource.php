<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicPlan */
class StrategicPlanResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'vision' => $this->vision,
            'mission' => $this->mission,
            'strategic_context' => $this->strategic_context,
            'core_values' => $this->core_values,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration,
            'status' => $this->status,
            'version' => $this->version,
            'parent_plan_id' => $this->parent_plan_id,
            'owner_user_id' => $this->owner_user_id,
            'department_id' => $this->department_id,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'themes' => StrategicThemeResource::collection($this->whenLoaded('themes')),
            'priorities' => StrategicPlanPriorityResource::collection($this->whenLoaded('priorities')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'goals' => StrategicGoalResource::collection($this->whenLoaded('goals')),
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
