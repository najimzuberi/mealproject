<?php

namespace App\Modules\Project\Resources;

use App\Modules\Project\Models\ProjectAssumption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectAssumption */
class ProjectAssumptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'description' => $this->description,
        ];
    }
}
