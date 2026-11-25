<?php

namespace App\Modules\StrategicPlanning\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStrategicObjectiveRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')->where('organization_id', $organizationId)],
            'responsible_user_ids' => ['nullable', 'array'],
            'responsible_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
