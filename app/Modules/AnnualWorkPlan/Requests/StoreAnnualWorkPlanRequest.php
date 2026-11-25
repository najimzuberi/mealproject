<?php

namespace App\Modules\AnnualWorkPlan\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnualWorkPlanRequest extends FormRequest
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
            'strategic_plan_id' => ['required', 'integer', Rule::exists('strategic_plans', 'id')->where('organization_id', $organizationId)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('annual_work_plans', 'code')->where('organization_id', $organizationId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'planning_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string'],
            'priorities' => ['nullable', 'array'],
            'priorities.*.strategic_objective_id' => ['required_with:priorities', 'integer', Rule::exists('strategic_objectives', 'id')->where('organization_id', $organizationId)],
            'priorities.*.title' => ['required_with:priorities', 'string', 'max:255'],
            'priorities.*.description' => ['nullable', 'string'],
            'priorities.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'priorities.*.activities' => ['nullable', 'array'],
            'priorities.*.activities.*.title' => ['required_with:priorities.*.activities', 'string', 'max:255'],
            'priorities.*.activities.*.description' => ['nullable', 'string'],
            'priorities.*.activities.*.deliverable' => ['nullable', 'string', 'max:255'],
            'priorities.*.activities.*.budget_estimate' => ['nullable', 'numeric', 'min:0'],
            'priorities.*.activities.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'priorities.*.activities.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priorities.*.activities.*.starts_on' => ['nullable', 'date'],
            'priorities.*.activities.*.ends_on' => ['nullable', 'date', 'after_or_equal:priorities.*.activities.*.starts_on'],
            'priorities.*.activities.*.milestones' => ['nullable', 'array'],
            'priorities.*.activities.*.milestones.*.quarter' => ['required_with:priorities.*.activities.*.milestones', Rule::in(['Q1', 'Q2', 'Q3', 'Q4'])],
            'priorities.*.activities.*.milestones.*.title' => ['required_with:priorities.*.activities.*.milestones', 'string', 'max:255'],
            'priorities.*.activities.*.milestones.*.due_on' => ['nullable', 'date'],
        ];
    }
}
