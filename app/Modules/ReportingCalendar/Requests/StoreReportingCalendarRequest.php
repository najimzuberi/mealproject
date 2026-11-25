<?php

namespace App\Modules\ReportingCalendar\Requests;

use App\Enums\RecordStatus;
use App\Enums\ReportingFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportingCalendarRequest extends FormRequest
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
            'frequency' => ['required', Rule::enum(ReportingFrequency::class)],
            'description' => ['nullable', 'string'],
            'allow_overlapping_periods' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
            'periods' => ['nullable', 'array'],
            'periods.*.name' => ['required_with:periods', 'string', 'max:255'],
            'periods.*.starts_on' => ['required_with:periods', 'date'],
            'periods.*.ends_on' => ['required_with:periods', 'date'],
        ];
    }
}
