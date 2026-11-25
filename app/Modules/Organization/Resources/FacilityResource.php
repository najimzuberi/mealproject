<?php

namespace App\Modules\Organization\Resources;

use App\Modules\Organization\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Facility */
class FacilityResource extends JsonResource
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
            'office_id' => $this->office_id,
            'facility_type_id' => $this->facility_type_id,
            'geographic_unit_id' => $this->geographic_unit_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'registration_number' => $this->registration_number,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
