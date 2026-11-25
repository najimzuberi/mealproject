<?php

namespace App\Modules\ResultsFramework\Resources;

use App\Modules\ResultsFramework\Models\ResultsFramework;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ResultsFramework */
class ResultsFrameworkResource extends JsonResource
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
            'project_id' => $this->project_id,
            'title' => $this->title,
            'theory_of_change' => $this->theory_of_change,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'version' => $this->version,
            'parent_framework_id' => $this->parent_framework_id,
            'results' => $this->whenLoaded('results'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
