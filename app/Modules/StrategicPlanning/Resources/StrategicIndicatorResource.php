<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicIndicator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicIndicator */
class StrategicIndicatorResource extends JsonResource
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
            'strategic_outcome_id' => $this->strategic_outcome_id,
            'department_id' => $this->department_id,
            'indicator_id' => $this->indicator_id,
            'code' => $this->code,
            'name' => $this->name,
            'definition' => $this->definition,
            'measurement_unit' => $this->measurement_unit,
            'measurement_direction' => $this->measurement_direction,
            'baseline_value' => $this->baseline_value,
            'baseline_date' => $this->baseline_date?->toDateString(),
            'target_value' => $this->target_value,
            'target_period' => $this->target_period,
            'data_source' => $this->data_source,
            'measurement_frequency' => $this->measurement_frequency,
            'status' => $this->status,
        ];
    }
}
