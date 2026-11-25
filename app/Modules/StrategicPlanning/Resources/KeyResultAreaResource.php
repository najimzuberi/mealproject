<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\KeyResultArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KeyResultArea */
class KeyResultAreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'strategic_objective_id' => $this->strategic_objective_id,
            'department_id' => $this->department_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
