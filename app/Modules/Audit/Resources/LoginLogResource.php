<?php

namespace App\Modules\Audit\Resources;

use App\Modules\Audit\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoginLog */
class LoginLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'email' => $this->email,
            'event' => $this->event,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
        ];
    }
}
