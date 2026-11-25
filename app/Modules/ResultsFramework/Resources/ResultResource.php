<?php

namespace App\Modules\ResultsFramework\Resources;

use App\Modules\ResultsFramework\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Result */
class ResultResource extends JsonResource
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
            'results_framework_id' => $this->results_framework_id,
            'parent_id' => $this->parent_id,
            'level' => $this->level instanceof \BackedEnum ? $this->level->value : $this->level,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'department_id' => $this->department_id,
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
