<?php

namespace App\Modules\ReportingCalendar\Requests;

use App\Enums\RecordStatus;
use App\Enums\ReportingFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportingCalendarRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'frequency' => ['sometimes', Rule::enum(ReportingFrequency::class)],
            'description' => ['nullable', 'string'],
            'allow_overlapping_periods' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }
}
