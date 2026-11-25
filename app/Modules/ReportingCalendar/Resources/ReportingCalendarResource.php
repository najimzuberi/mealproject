<?php

namespace App\Modules\ReportingCalendar\Resources;

use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportingCalendar */
class ReportingCalendarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'frequency' => $this->frequency,
            'description' => $this->description,
            'allow_overlapping_periods' => $this->allow_overlapping_periods,
            'status' => $this->status,
            'periods' => ReportingPeriodResource::collection($this->whenLoaded('periods')),
        ];
    }
}
