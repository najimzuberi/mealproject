<?php

namespace App\Modules\ReportingCalendar\Resources;

use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportingPeriod */
class ReportingPeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'reporting_calendar_id' => $this->reporting_calendar_id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'submission_deadline' => $this->submission_deadline?->toDateString(),
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closure_notes' => $this->closure_notes,
            'responsible_officer_id' => $this->responsible_officer_id,
            'reminder_days_before' => $this->reminder_days_before,
        ];
    }
}
