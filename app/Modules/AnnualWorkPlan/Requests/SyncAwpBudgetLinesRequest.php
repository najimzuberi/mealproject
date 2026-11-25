<?php

namespace App\Modules\AnnualWorkPlan\Requests;

use App\Shared\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAwpBudgetLinesRequest extends FormRequest
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
            'budget_lines' => ['required', 'array'],
            'budget_lines.*.awp_activity_id' => [
                'required',
                'integer',
                Rule::exists('awp_activities', 'id')->where('organization_id', $organizationId),
            ],
            'budget_lines.*.amount' => ['required', 'numeric', 'min:0'],
            'budget_lines.*.currency' => ['nullable', 'string', 'size:3'],
            'budget_lines.*.code' => ['nullable', 'string', 'max:50'],
            'budget_lines.*.category' => ['nullable', 'string', 'max:100'],
            'budget_lines.*.description' => ['nullable', 'string'],
            'budget_lines.*.funding_source' => ['nullable', 'string', 'max:255'],
            'budget_lines.*.financial_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'budget_lines.*.quarter' => ['nullable', 'string', 'in:Q1,Q2,Q3,Q4'],
            'budget_lines.*.department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
