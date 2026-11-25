<?php

namespace App\Modules\AccessControl\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
