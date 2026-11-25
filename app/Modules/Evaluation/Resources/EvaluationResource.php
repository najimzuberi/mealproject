<?php

namespace App\Modules\Evaluation\Resources;

use App\Modules\Evaluation\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Evaluation */
class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'title' => $this->title,
            'evaluation_type' => $this->evaluation_type,
            'programme_id' => $this->programme_id,
            'project_id' => $this->project_id,
            'purpose' => $this->purpose,
            'scope' => $this->scope,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'evaluation_lead_id' => $this->evaluation_lead_id,
            'evaluation_team' => $this->evaluation_team,
            'methodology' => $this->methodology,
            'data_collection_methods' => $this->data_collection_methods,
            'target_population' => $this->target_population,
            'sample_size' => $this->sample_size,
            'geographic_coverage' => $this->geographic_coverage,
            'tools' => $this->tools,
            'timeline_notes' => $this->timeline_notes,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'questions' => $this->whenLoaded('questions'),
            'findings' => $this->whenLoaded('findings'),
            'recommendations' => $this->whenLoaded('recommendations'),
            'approval_histories' => $this->whenLoaded('approvalHistories'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
