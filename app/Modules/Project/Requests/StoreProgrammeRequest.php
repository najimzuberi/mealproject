<?php

namespace App\Modules\Project\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgrammeRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('programmes', 'code')->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'strategic_alignment' => ['nullable', 'string'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'programme_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'strategic_plan_id' => ['nullable', 'integer', Rule::exists('strategic_plans', 'id')->where('organization_id', $organizationId)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'budget_envelope' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_information' => ['nullable', 'string'],
            'partners' => ['nullable', 'array'],
            'target_groups' => ['nullable', 'array'],
            'thematic_area' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'team_members' => ['nullable', 'array'],
            'team_members.*.user_id' => ['required_with:team_members', 'integer', 'exists:users,id'],
            'team_members.*.role_label' => ['nullable', 'string', 'max:100'],
            'geographic_unit_ids' => ['nullable', 'array'],
            'geographic_unit_ids.*' => ['integer', Rule::exists('geographic_units', 'id')->where('organization_id', $organizationId)],
            'stakeholders' => ['nullable', 'array'],
            'stakeholders.*.name' => ['required_with:stakeholders', 'string', 'max:255'],
        ];
    }
}
