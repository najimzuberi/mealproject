<?php

namespace App\Modules\StrategicPlanning\Resources;

use App\Modules\StrategicPlanning\Models\StrategicTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StrategicTheme */
class StrategicThemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'strategic_plan_id' => $this->strategic_plan_id,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }
}
