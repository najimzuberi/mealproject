<?php

namespace App\Modules\Project\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProjectGeographicUnitsRequest extends FormRequest
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
        $organizationId = app(OrganizationContext::class)->id();

        return [
            'geographic_unit_ids' => ['required', 'array'],
            'geographic_unit_ids.*' => [
                'integer',
                Rule::exists('geographic_units', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
