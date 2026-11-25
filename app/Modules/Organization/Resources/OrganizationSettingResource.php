<?php

namespace App\Modules\Organization\Resources;

use App\Modules\Organization\Models\OrganizationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationSetting */
class OrganizationSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
