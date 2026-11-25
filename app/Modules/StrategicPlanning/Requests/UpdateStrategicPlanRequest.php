<?php

namespace App\Modules\StrategicPlanning\Requests;

use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStrategicPlanRequest extends FormRequest
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
        /** @var StrategicPlan|null $plan */
        $plan = $this->route('strategic_plan');
        $organizationId = app(OrganizationContext::class)->id();

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('strategic_plans', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($plan?->id),
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'strategic_context' => ['nullable', 'string'],
            'core_values' => ['nullable', 'array'],
            'core_values.*' => ['string', 'max:255'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'priorities' => ['nullable', 'array'],
            'priorities.*.title' => ['required_with:priorities', 'string', 'max:255'],
            'priorities.*.code' => ['nullable', 'string', 'max:50'],
            'priorities.*.description' => ['nullable', 'string'],
            'priorities.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'priorities.*.status' => ['nullable', 'string', 'max:50'],
            'priorities.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'themes' => ['nullable', 'array'],
            'themes.*.name' => ['required_with:themes', 'string', 'max:255'],
            'themes.*.description' => ['nullable', 'string'],
            'themes.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals' => ['nullable', 'array'],
            'goals.*.title' => ['required_with:goals', 'string', 'max:255'],
            'goals.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.description' => ['nullable', 'string'],
            'goals.*.expected_result' => ['nullable', 'string'],
            'goals.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.strategic_theme_id' => ['nullable', 'integer'],
            'goals.*.theme_index' => ['nullable', 'integer', 'min:0'],
            'goals.*.department_ids' => ['nullable', 'array'],
            'goals.*.department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives' => ['nullable', 'array'],
            'goals.*.objectives.*.title' => ['required_with:goals.*.objectives', 'string', 'max:255'],
            'goals.*.objectives.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.description' => ['nullable', 'string'],
            'goals.*.objectives.*.expected_result' => ['nullable', 'string'],
            'goals.*.objectives.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.department_ids' => ['nullable', 'array'],
            'goals.*.objectives.*.department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives.*.responsible_user_ids' => ['nullable', 'array'],
            'goals.*.objectives.*.responsible_user_ids.*' => ['integer', 'exists:users,id'],
            'goals.*.objectives.*.kra' => ['nullable', 'array'],
            'goals.*.objectives.*.kra.*.title' => ['required_with:goals.*.objectives.*.kra', 'string', 'max:255'],
            'goals.*.objectives.*.kra.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.kra.*.description' => ['nullable', 'string'],
            'goals.*.objectives.*.kra.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
