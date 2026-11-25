<?php

namespace App\Modules\Organization\Resources;

use App\Modules\Organization\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Department */
class DepartmentResource extends JsonResource
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
            'head_user_id' => $this->head_user_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
