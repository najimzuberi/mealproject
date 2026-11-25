<?php

namespace App\Modules\ReportingCalendar\Requests;

use App\Enums\ReportingPeriodStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportingPeriodRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'submission_deadline' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'responsible_officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['nullable', Rule::enum(ReportingPeriodStatus::class)],
        ];
    }
}
