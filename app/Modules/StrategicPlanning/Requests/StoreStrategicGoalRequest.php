<?php

namespace App\Modules\StrategicPlanning\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStrategicGoalRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'strategic_theme_id' => ['nullable', 'integer'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'objectives' => ['nullable', 'array'],
            'objectives.*.title' => ['required_with:objectives', 'string', 'max:255'],
        ];
    }
}
