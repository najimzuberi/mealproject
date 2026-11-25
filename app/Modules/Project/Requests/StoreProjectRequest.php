<?php

namespace App\Modules\Project\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')->where('organization_id', $organizationId)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'code')->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'background' => ['nullable', 'string'],
            'problem_statement' => ['nullable', 'string'],
            'target_groups' => ['nullable', 'array'],
            'strategic_plan_id' => ['nullable', 'integer', Rule::exists('strategic_plans', 'id')->where('organization_id', $organizationId)],
            'strategic_objective_id' => ['nullable', 'integer', Rule::exists('strategic_objectives', 'id')->where('organization_id', $organizationId)],
            'annual_work_plan_id' => ['nullable', 'integer', Rule::exists('annual_work_plans', 'id')->where('organization_id', $organizationId)],
            'sector_id' => ['nullable', 'integer', Rule::exists('sectors', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'project_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_information' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'activities' => ['nullable', 'array'],
            'activities.*.title' => ['required_with:activities', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string'],
            'activities.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'activities.*.starts_on' => ['required_with:activities', 'date'],
            'activities.*.ends_on' => ['required_with:activities', 'date', 'after_or_equal:activities.*.starts_on'],
            'activities.*.milestones' => ['nullable', 'array'],
            'activities.*.milestones.*.title' => ['required_with:activities.*.milestones', 'string', 'max:255'],
            'activities.*.milestones.*.due_on' => ['nullable', 'date'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.title' => ['required_with:milestones', 'string', 'max:255'],
            'milestones.*.due_on' => ['nullable', 'date'],
            'assumptions' => ['nullable', 'array'],
            'assumptions.*.description' => ['required_with:assumptions', 'string'],
            'risks' => ['nullable', 'array'],
            'risks.*.description' => ['required_with:risks', 'string'],
            'risks.*.likelihood' => ['nullable', 'string', 'max:50'],
            'risks.*.impact' => ['nullable', 'string', 'max:50'],
            'risks.*.mitigation' => ['nullable', 'string'],
        ];
    }
}
