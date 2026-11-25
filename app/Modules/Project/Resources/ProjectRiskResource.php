<?php

namespace App\Modules\Project\Resources;

use App\Modules\Project\Models\ProjectRisk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectRisk */
class ProjectRiskResource extends JsonResource
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
            'likelihood' => $this->likelihood,
            'impact' => $this->impact,
            'mitigation' => $this->mitigation,
            'status' => $this->status,
        ];
    }
}
