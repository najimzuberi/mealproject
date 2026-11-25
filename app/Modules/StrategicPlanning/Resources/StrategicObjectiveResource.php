<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicObjective;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicObjective */
class StrategicObjectiveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'strategic_goal_id' => $this->strategic_goal_id,
            'code' => $this->code,
            'title' => $this->title,
            'priority' => $this->priority,
            'description' => $this->description,
            'expected_result' => $this->expected_result,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'department_ids' => $this->whenLoaded('departments', fn () => $this->departments->pluck('id')->values()),
            'responsible_user_ids' => $this->whenLoaded('responsibleUsers', fn () => $this->responsibleUsers->pluck('id')->values()),
            'kra' => KeyResultAreaResource::collection($this->whenLoaded('keyResultAreas')),
            'outcomes' => StrategicOutcomeResource::collection($this->whenLoaded('outcomes')),
            'indicators' => StrategicIndicatorResource::collection($this->whenLoaded('indicators')),
        ];
    }
}
