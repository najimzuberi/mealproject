<?php

namespace App\Modules\Organization\Resources;

use App\Modules\Organization\Models\Organization;
use App\Modules\Sector\Resources\SectorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Organization */
class OrganizationResource extends JsonResource
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
            'code' => $this->code,
            'registration_number' => $this->registration_number,
            'type' => $this->type,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : null,
            'country' => $this->country,
            'headquarters_region' => $this->headquarters_region,
            'physical_address' => $this->physical_address,
            'postal_address' => $this->postal_address,
            'address' => $this->address,
            'timezone' => $this->timezone,
            'default_language' => $this->default_language,
            'default_currency' => $this->default_currency,
            'status' => $this->status,
            'sectors' => SectorResource::collection($this->whenLoaded('sectors')),
            'settings' => OrganizationSettingResource::collection($this->whenLoaded('settings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
