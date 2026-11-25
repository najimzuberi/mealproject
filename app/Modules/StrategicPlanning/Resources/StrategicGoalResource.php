<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicGoal */
class StrategicGoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'strategic_plan_id' => $this->strategic_plan_id,
            'strategic_theme_id' => $this->strategic_theme_id,
            'code' => $this->code,
            'title' => $this->title,
            'category' => $this->category,
            'priority' => $this->priority,
            'owner_user_id' => $this->owner_user_id,
            'description' => $this->description,
            'expected_result' => $this->expected_result,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'department_ids' => $this->whenLoaded('departments', fn () => $this->departments->pluck('id')->values()),
            'objectives' => StrategicObjectiveResource::collection($this->whenLoaded('objectives')),
        ];
    }
}
