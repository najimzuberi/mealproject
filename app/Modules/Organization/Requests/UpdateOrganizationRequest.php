<?php

namespace App\Modules\Organization\Requests;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
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
        $organization = $this->route('organization');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('organizations', 'code')->ignore($organization?->id)],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(OrganizationType::class)],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url'],
            'country' => ['nullable', 'string', 'max:100'],
            'headquarters_region' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string'],
            'postal_address' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'default_language' => ['nullable', 'string', 'max:20'],
            'default_currency' => ['nullable', 'string', 'max:10'],
        ];
    }
}
