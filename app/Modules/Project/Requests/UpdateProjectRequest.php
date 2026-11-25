<?php

namespace App\Modules\Project\Requests;

use App\Modules\Project\Models\Project;
use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
        /** @var Project|null $project */
        $project = $this->route('project');
        $organizationId = app(OrganizationContext::class)->id();

        return [
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')->where('organization_id', $organizationId)],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('projects', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($project?->id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'starts_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'date', 'after_or_equal:starts_on'],
            'activities' => ['nullable', 'array'],
            'activities.*.title' => ['required_with:activities', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string'],
            'activities.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'activities.*.starts_on' => ['required_with:activities', 'date'],
            'activities.*.ends_on' => ['required_with:activities', 'date'],
            'activities.*.milestones' => ['nullable', 'array'],
            'activities.*.milestones.*.title' => ['required_with:activities.*.milestones', 'string', 'max:255'],
            'milestones' => ['nullable', 'array'],
            'assumptions' => ['nullable', 'array'],
            'risks' => ['nullable', 'array'],
        ];
    }
}
