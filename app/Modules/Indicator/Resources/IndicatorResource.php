<?php

namespace App\Modules\Indicator\Resources;

use App\Modules\Indicator\Models\Indicator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Indicator */
class IndicatorResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'definition' => $this->definition,
            'measurement_unit' => $this->measurement_unit,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'formula' => $this->formula,
            'aggregation_method' => $this->aggregation_method,
            'direction_of_change' => $this->direction_of_change,
            'result_level' => $this->result_level instanceof \BackedEnum ? $this->result_level->value : $this->result_level,
            'strategic_objective_id' => $this->strategic_objective_id,
            'project_id' => $this->project_id,
            'results_framework_id' => $this->results_framework_id,
            'result_id' => $this->result_id,
            'indicator_type' => $this->indicator_type,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'version' => $this->version,
            'parent_indicator_id' => $this->parent_indicator_id,
            'source_url' => $this->source_url,
            'source_organization' => $this->source_organization,
            'source_accessed_on' => $this->source_accessed_on?->toDateString(),
            'adaptation_notes' => $this->adaptation_notes,
            'data_source' => $this->data_source,
            'collection_method' => $this->collection_method,
            'collection_frequency' => $this->collection_frequency,
            'responsible_collector_id' => $this->responsible_collector_id,
            'supervisor_id' => $this->supervisor_id,
            'me_validator_id' => $this->me_validator_id,
            'means_of_verification' => $this->means_of_verification,
            'validation_rules' => $this->validation_rules,
            'limitations' => $this->limitations,
            'assumptions' => $this->assumptions,
            'quality_checks' => $this->quality_checks,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'baselines' => $this->whenLoaded('baselines'),
            'targets' => $this->whenLoaded('targets'),
            'disaggregations' => $this->whenLoaded('disaggregations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
