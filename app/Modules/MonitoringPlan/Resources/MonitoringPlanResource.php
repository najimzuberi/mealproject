<?php

namespace App\Modules\MonitoringPlan\Resources;

use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MonitoringPlan */
class MonitoringPlanResource extends JsonResource
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
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'version' => $this->version,
            'parent_monitoring_plan_id' => $this->parent_monitoring_plan_id,
            'intended_use' => $this->intended_use,
            'assumptions' => $this->assumptions,
            'limitations' => $this->limitations,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by' => $this->approved_by,
            'created_by' => $this->created_by,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'indicator_id' => $item->indicator_id,
                'indicator' => $item->relationLoaded('indicator') ? [
                    'id' => $item->indicator->id,
                    'code' => $item->indicator->code,
                    'name' => $item->indicator->name,
                    'version' => $item->indicator->version,
                ] : null,
                'baseline_value' => $item->baseline_value,
                'target_value' => $item->target_value,
                'data_source' => $item->data_source,
                'collection_method' => $item->collection_method,
                'collection_frequency' => $item->collection_frequency,
                'collection_deadline' => $item->collection_deadline?->toDateString(),
                'responsible_collector_id' => $item->responsible_collector_id,
                'supervisor_id' => $item->supervisor_id,
                'me_validator_id' => $item->me_validator_id,
                'means_of_verification' => $item->means_of_verification,
                'validation_rules' => $item->validation_rules,
                'reporting_format' => $item->reporting_format,
                'intended_use' => $item->intended_use,
                'assumptions' => $item->assumptions,
                'limitations' => $item->limitations,
                'sort_order' => $item->sort_order,
            ])),
            'approval_histories' => $this->whenLoaded('approvalHistories'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
