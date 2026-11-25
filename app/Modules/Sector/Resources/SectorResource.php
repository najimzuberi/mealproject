<?php

namespace App\Modules\Sector\Resources;

use App\Modules\Sector\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sector */
class SectorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'status' => $this->status,
        ];
    }
}
