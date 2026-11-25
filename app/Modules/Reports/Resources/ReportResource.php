<?php

namespace App\Modules\Reports\Resources;

use App\Modules\Reports\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'title' => $this->title,
            'report_type' => $this->report_type instanceof \BackedEnum ? $this->report_type->value : $this->report_type,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'programme_id' => $this->programme_id,
            'project_id' => $this->project_id,
            'reporting_period_id' => $this->reporting_period_id,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'prepared_by' => $this->prepared_by,
            'reviewed_by' => $this->reviewed_by,
            'approved_by' => $this->approved_by,
            'executive_summary' => $this->executive_summary,
            'performance_summary' => $this->performance_summary,
            'challenges' => $this->challenges,
            'risks' => $this->risks,
            'recommendations' => $this->recommendations,
            'ai_assisted' => $this->ai_assisted,
            'snapshot' => $this->snapshot,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'indicator_results' => ReportIndicatorResultResource::collection($this->whenLoaded('indicatorResults')),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
            ]),
            'reporting_period' => $this->whenLoaded('reportingPeriod', fn () => [
                'id' => $this->reportingPeriod?->id,
                'name' => $this->reportingPeriod?->name,
            ]),
            'approval_histories' => $this->whenLoaded('approvalHistories'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
