<?php

namespace App\Modules\AccessControl\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permission_names' => ['required', 'array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
