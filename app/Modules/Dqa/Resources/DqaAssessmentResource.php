<?php

namespace App\Modules\Dqa\Resources;

use App\Modules\Dqa\Models\DqaAssessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DqaAssessment */
class DqaAssessmentResource extends JsonResource
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
            'submission_id' => $this->submission_id,
            'assessed_by' => $this->assessed_by,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'validity_score' => $this->validity_score,
            'reliability_score' => $this->reliability_score,
            'completeness_score' => $this->completeness_score,
            'timeliness_score' => $this->timeliness_score,
            'precision_score' => $this->precision_score,
            'integrity_score' => $this->integrity_score,
            'overall_score' => $this->overall_score,
            'summary' => $this->summary,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'submission' => $this->whenLoaded('submission'),
            'issues' => DqaIssueResource::collection($this->whenLoaded('issues')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
