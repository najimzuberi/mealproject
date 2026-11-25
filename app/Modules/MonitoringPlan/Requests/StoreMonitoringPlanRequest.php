<?php

namespace App\Modules\MonitoringPlan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonitoringPlanRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'intended_use' => ['nullable', 'string'],
            'assumptions' => ['nullable', 'string'],
            'limitations' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.indicator_id' => ['required_with:items', 'integer', 'exists:indicators,id'],
            'items.*.baseline_value' => ['nullable', 'string', 'max:255'],
            'items.*.target_value' => ['nullable', 'string', 'max:255'],
            'items.*.data_source' => ['nullable', 'string', 'max:255'],
            'items.*.collection_method' => ['nullable', 'string', 'max:255'],
            'items.*.collection_frequency' => ['nullable', 'string', 'max:100'],
            'items.*.collection_deadline' => ['nullable', 'date'],
            'items.*.responsible_collector_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.me_validator_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.means_of_verification' => ['nullable', 'string'],
            'items.*.validation_rules' => ['nullable', 'string'],
            'items.*.reporting_format' => ['nullable', 'string', 'max:255'],
            'items.*.intended_use' => ['nullable', 'string'],
            'items.*.assumptions' => ['nullable', 'string'],
            'items.*.limitations' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
