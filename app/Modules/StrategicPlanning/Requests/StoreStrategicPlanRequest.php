<?php

namespace App\Modules\StrategicPlanning\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStrategicPlanRequest extends FormRequest
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
                Rule::unique('strategic_plans', 'code')->where('organization_id', $organizationId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'strategic_context' => ['nullable', 'string'],
            'core_values' => ['nullable', 'array'],
            'core_values.*' => ['string', 'max:255'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
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
            'goals.*.category' => ['nullable', 'string', 'max:100'],
            'goals.*.priority' => ['nullable', 'integer', 'min:0'],
            'goals.*.owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'goals.*.description' => ['nullable', 'string'],
            'goals.*.expected_result' => ['nullable', 'string'],
            'goals.*.start_date' => ['nullable', 'date'],
            'goals.*.end_date' => ['nullable', 'date', 'after_or_equal:goals.*.start_date'],
            'goals.*.status' => ['nullable', 'string', 'max:50'],
            'goals.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.strategic_theme_id' => ['nullable', 'integer'],
            'goals.*.theme_index' => ['nullable', 'integer', 'min:0'],
            'goals.*.department_ids' => ['nullable', 'array'],
            'goals.*.department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives' => ['nullable', 'array'],
            'goals.*.objectives.*.title' => ['required_with:goals.*.objectives', 'string', 'max:255'],
            'goals.*.objectives.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.priority' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.description' => ['nullable', 'string'],
            'goals.*.objectives.*.expected_result' => ['nullable', 'string'],
            'goals.*.objectives.*.start_date' => ['nullable', 'date'],
            'goals.*.objectives.*.end_date' => ['nullable', 'date', 'after_or_equal:goals.*.objectives.*.start_date'],
            'goals.*.objectives.*.status' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.department_ids' => ['nullable', 'array'],
            'goals.*.objectives.*.department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives.*.responsible_user_ids' => ['nullable', 'array'],
            'goals.*.objectives.*.responsible_user_ids.*' => ['integer', 'exists:users,id'],
            'goals.*.objectives.*.kra' => ['nullable', 'array'],
            'goals.*.objectives.*.kra.*.title' => ['required_with:goals.*.objectives.*.kra', 'string', 'max:255'],
            'goals.*.objectives.*.kra.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.kra.*.description' => ['nullable', 'string'],
            'goals.*.objectives.*.kra.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives.*.kra.*.priority' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.kra.*.status' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.kra.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.outcomes' => ['nullable', 'array'],
            'goals.*.objectives.*.outcomes.*.title' => ['required_with:goals.*.objectives.*.outcomes', 'string', 'max:255'],
            'goals.*.objectives.*.outcomes.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.outcomes.*.description' => ['nullable', 'string'],
            'goals.*.objectives.*.outcomes.*.expected_change' => ['nullable', 'string'],
            'goals.*.objectives.*.outcomes.*.target_period' => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.outcomes.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives.*.outcomes.*.status' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.outcomes.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'goals.*.objectives.*.outcomes.*.indicators' => ['nullable', 'array'],
            'goals.*.objectives.*.outcomes.*.indicators.*.name' => ['required_with:goals.*.objectives.*.outcomes.*.indicators', 'string', 'max:255'],
            'goals.*.objectives.*.indicators' => ['nullable', 'array'],
            'goals.*.objectives.*.indicators.*.name' => ['required_with:goals.*.objectives.*.indicators', 'string', 'max:255'],
            'goals.*.objectives.*.indicators.*.code' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.indicators.*.definition' => ['nullable', 'string'],
            'goals.*.objectives.*.indicators.*.measurement_unit' => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.indicators.*.measurement_direction' => ['nullable', 'string', 'max:50'],
            'goals.*.objectives.*.indicators.*.baseline_value' => ['nullable', 'numeric'],
            'goals.*.objectives.*.indicators.*.baseline_date' => ['nullable', 'date'],
            'goals.*.objectives.*.indicators.*.target_value' => ['nullable', 'numeric'],
            'goals.*.objectives.*.indicators.*.target_period' => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.indicators.*.data_source' => ['nullable', 'string', 'max:255'],
            'goals.*.objectives.*.indicators.*.measurement_frequency' => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.indicators.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'goals.*.objectives.*.indicators.*.status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
