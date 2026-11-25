<?php

namespace App\Modules\Geography\Resources;

use App\Modules\Geography\Models\GeographicUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GeographicUnit */
class GeographicUnitResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'geographic_type_id' => $this->geographic_type_id,
            'name' => $this->name,
            'code' => $this->code,
            'level' => $this->level,
            'type' => $this->type,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'children' => self::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
