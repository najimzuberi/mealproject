<?php

namespace App\Modules\DataCollection\Resources;

use App\Modules\DataCollection\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Submission */
class SubmissionResource extends JsonResource
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
            'reporting_assignment_id' => $this->reporting_assignment_id,
            'monitoring_plan_id' => $this->monitoring_plan_id,
            'monitoring_plan_item_id' => $this->monitoring_plan_item_id,
            'indicator_id' => $this->indicator_id,
            'reporting_period_id' => $this->reporting_period_id,
            'submitted_by' => $this->submitted_by,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'value' => $this->value,
            'disaggregations' => $this->disaggregations,
            'notes' => $this->notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'locked_at' => $this->locked_at?->toIso8601String(),
            'indicator' => $this->whenLoaded('indicator', fn () => [
                'id' => $this->indicator?->id,
                'code' => $this->indicator?->code,
                'name' => $this->indicator?->name,
            ]),
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'id' => $v->id,
                'dimension' => $v->dimension,
                'label' => $v->label,
                'value' => $v->value,
            ])),
            'approval_histories' => $this->whenLoaded('approvalHistories'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
