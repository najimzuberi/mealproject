<?php

namespace App\Modules\Indicator\Requests;

use App\Enums\ResultLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'definition' => ['nullable', 'string'],
            'measurement_unit' => ['nullable', 'string', 'max:100'],
            'numerator' => ['nullable', 'string', 'max:255'],
            'denominator' => ['nullable', 'string', 'max:255'],
            'formula' => ['nullable', 'string', 'max:255'],
            'aggregation_method' => ['nullable', 'string', 'max:100'],
            'direction_of_change' => ['nullable', 'string', 'max:50'],
            'result_level' => ['nullable', Rule::enum(ResultLevel::class)],
            'strategic_objective_id' => ['nullable', 'integer', 'exists:strategic_objectives,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'results_framework_id' => ['nullable', 'integer', 'exists:results_frameworks,id'],
            'result_id' => ['nullable', 'integer', 'exists:results,id'],
            'indicator_type' => ['nullable', 'string', 'max:50'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_organization' => ['nullable', 'string', 'max:255'],
            'source_accessed_on' => ['nullable', 'date'],
            'adaptation_notes' => ['nullable', 'string'],
            'data_source' => ['nullable', 'string', 'max:255'],
            'collection_method' => ['nullable', 'string', 'max:255'],
            'collection_frequency' => ['nullable', 'string', 'max:100'],
            'responsible_collector_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'me_validator_id' => ['nullable', 'integer', 'exists:users,id'],
            'means_of_verification' => ['nullable', 'string'],
            'validation_rules' => ['nullable', 'string'],
            'limitations' => ['nullable', 'string'],
            'assumptions' => ['nullable', 'string'],
            'quality_checks' => ['nullable', 'string'],
            'baselines' => ['nullable', 'array'],
            'baselines.*.value' => ['required_with:baselines', 'string', 'max:255'],
            'baselines.*.baseline_date' => ['nullable', 'date'],
            'baselines.*.notes' => ['nullable', 'string'],
            'targets' => ['nullable', 'array'],
            'targets.*.value' => ['required_with:targets', 'string', 'max:255'],
            'targets.*.target_period' => ['nullable', 'string', 'max:100'],
            'targets.*.starts_on' => ['nullable', 'date'],
            'targets.*.ends_on' => ['nullable', 'date'],
            'targets.*.is_cumulative' => ['nullable', 'boolean'],
            'targets.*.notes' => ['nullable', 'string'],
            'disaggregations' => ['nullable', 'array'],
            'disaggregations.*.dimension' => ['required_with:disaggregations', 'string', 'max:100'],
            'disaggregations.*.label' => ['nullable', 'string', 'max:255'],
            'disaggregations.*.is_required' => ['nullable', 'boolean'],
        ];
    }
}
