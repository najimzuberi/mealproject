<?php

namespace App\Modules\Geography\Resources;

use App\Modules\Geography\Models\GeographicType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GeographicType */
class GeographicTypeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'default_level' => $this->default_level,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
