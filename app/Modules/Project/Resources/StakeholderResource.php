<?php

namespace App\Modules\Project\Resources;

use App\Modules\Project\Models\Stakeholder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Stakeholder */
class StakeholderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'type' => $this->type,
            'role' => $this->role,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
        ];
    }
}
