<?php

namespace App\Modules\Organization\Requests;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterOrganizationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'sector_ids' => ['nullable', 'array'],
            'sector_ids.*' => ['integer', 'exists:sectors,id'],
        ];
    }
}
