<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicPlanPriority;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicPlanPriority */
class StrategicPlanPriorityResource extends JsonResource
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
            'department_id' => $this->department_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
