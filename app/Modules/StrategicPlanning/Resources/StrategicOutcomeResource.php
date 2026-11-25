<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicOutcome */
class StrategicOutcomeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'strategic_plan_id' => $this->strategic_plan_id,
            'strategic_objective_id' => $this->strategic_objective_id,
            'department_id' => $this->department_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'expected_change' => $this->expected_change,
            'target_period' => $this->target_period,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'indicators' => StrategicIndicatorResource::collection($this->whenLoaded('indicators')),
        ];
    }
}
