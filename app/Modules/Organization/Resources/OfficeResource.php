<?php

namespace App\Modules\Organization\Resources;

use App\Modules\Organization\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Office */
class OfficeResource extends JsonResource
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
            'department_id' => $this->department_id,
            'parent_id' => $this->parent_id,
            'geographic_unit_id' => $this->geographic_unit_id,
            'manager_user_id' => $this->manager_user_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'address' => $this->address,
            'country' => $this->country,
            'region' => $this->region,
            'district' => $this->district,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
