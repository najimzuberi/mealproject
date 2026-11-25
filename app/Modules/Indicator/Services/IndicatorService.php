<?php

namespace App\Modules\Indicator\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\IndicatorStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Indicator\Models\IndicatorBaseline;
use App\Modules\Indicator\Models\IndicatorDisaggregation;
use App\Modules\Indicator\Models\IndicatorTarget;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndicatorService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Indicator
    {
        return DB::transaction(function () use ($data) {
            $indicator = Indicator::query()->create([
                ...$this->baseAttributes($data),
                'organization_id' => $this->organizationContext->id(),
                'status' => IndicatorStatus::Draft->value,
                'version' => 1,
                'created_by' => Auth::id(),
            ]);

            $this->syncMeasurement($indicator, $data);
            $this->auditService->log(AuditAction::Created, 'indicators', Indicator::class, $indicator->id);

            return $this->load($indicator);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Indicator $indicator, array $data): Indicator
    {
        $this->assertOrg($indicator);

        $status = $indicator->status instanceof IndicatorStatus ? $indicator->status->value : (string) $indicator->status;

        if (in_array($status, [IndicatorStatus::Approved->value, IndicatorStatus::Superseded->value], true)) {
            return $this->createVersion($indicator, $data);
        }

        if ($status === IndicatorStatus::Retired->value) {
            throw ValidationException::withMessages(['status' => ['Retired indicators cannot be updated.']]);
        }

        return DB::transaction(function () use ($indicator, $data) {
            $indicator->fill($this->baseAttributes($data))->save();
            $this->syncMeasurement($indicator, $data);
            $this->auditService->log(AuditAction::Updated, 'indicators', Indicator::class, $indicator->id);

            return $this->load($indicator->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(Indicator $indicator, array $data = []): Indicator
    {
        $this->assertOrg($indicator);

        return DB::transaction(function () use ($indicator, $data) {
            $rootId = $indicator->parent_indicator_id ?? $indicator->id;

            $new = Indicator::query()->create([
                ...$this->baseAttributes(array_merge($indicator->toArray(), $data)),
                'organization_id' => $indicator->organization_id,
                'code' => $data['code'] ?? $indicator->code,
                'status' => IndicatorStatus::Draft->value,
                'version' => $indicator->version + 1,
                'parent_indicator_id' => $rootId,
                'created_by' => Auth::id(),
                'approved_at' => null,
                'approved_by' => null,
            ]);

            if (($indicator->status instanceof IndicatorStatus ? $indicator->status->value : $indicator->status) === IndicatorStatus::Approved->value) {
                $indicator->status = IndicatorStatus::Superseded->value;
                $indicator->save();
            }

            $this->syncMeasurement($new, array_merge([
                'baselines' => $indicator->baselines->map->only(['value', 'baseline_date', 'notes'])->all(),
                'targets' => $indicator->targets->map->only(['value', 'target_period', 'starts_on', 'ends_on', 'is_cumulative', 'notes'])->all(),
                'disaggregations' => $indicator->disaggregations->map->only(['dimension', 'label', 'is_required'])->all(),
            ], $data));

            $this->auditService->log(AuditAction::Created, 'indicators', Indicator::class, $new->id, null, [
                'versioned_from' => $indicator->id,
            ]);

            return $this->load($new);
        });
    }

    public function submit(Indicator $indicator, ?string $comment = null): Indicator
    {
        $this->assertOrg($indicator);

        return $this->load($this->workflowService->transition(
            $indicator,
            IndicatorStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [IndicatorStatus::Draft->value, IndicatorStatus::Returned->value],
            $comment,
            'indicators',
        ));
    }

    public function returnForCorrection(Indicator $indicator, ?string $comment = null): Indicator
    {
        $this->assertOrg($indicator);

        return $this->load($this->workflowService->transition(
            $indicator,
            IndicatorStatus::Returned->value,
            ApprovalAction::Returned,
            [IndicatorStatus::UnderReview->value],
            $comment,
            'indicators',
            true,
        ));
    }

    public function approve(Indicator $indicator, ?string $comment = null): Indicator
    {
        $this->assertOrg($indicator);

        return $this->load($this->workflowService->transition(
            $indicator,
            IndicatorStatus::Approved->value,
            ApprovalAction::Approved,
            [IndicatorStatus::UnderReview->value],
            $comment,
            'indicators',
            false,
            true,
        ));
    }

    public function retire(Indicator $indicator, ?string $comment = null): Indicator
    {
        $this->assertOrg($indicator);

        return $this->load($this->workflowService->transition(
            $indicator,
            IndicatorStatus::Retired->value,
            ApprovalAction::Archived,
            [IndicatorStatus::Approved->value, IndicatorStatus::Draft->value, IndicatorStatus::UnderReview->value],
            $comment,
            'indicators',
            true,
        ));
    }

    /**
     * @return list<Indicator>
     */
    public function versionHistory(Indicator $indicator): array
    {
        $this->assertOrg($indicator);
        $rootId = $indicator->parent_indicator_id ?? $indicator->id;

        return Indicator::query()
            ->where('organization_id', $indicator->organization_id)
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_indicator_id', $rootId);
            })
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function baseAttributes(array $data): array
    {
        return collect($data)->only([
            'code', 'name', 'description', 'definition', 'measurement_unit', 'numerator', 'denominator',
            'formula', 'aggregation_method', 'direction_of_change', 'result_level', 'strategic_objective_id',
            'project_id', 'results_framework_id', 'result_id', 'indicator_type', 'source_url', 'source_organization',
            'source_accessed_on', 'adaptation_notes', 'data_source', 'collection_method', 'collection_frequency',
            'responsible_collector_id', 'supervisor_id', 'me_validator_id', 'means_of_verification',
            'validation_rules', 'limitations', 'assumptions', 'quality_checks',
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncMeasurement(Indicator $indicator, array $data): void
    {
        if (array_key_exists('baselines', $data)) {
            $indicator->baselines()->delete();
            foreach ($data['baselines'] ?? [] as $baseline) {
                IndicatorBaseline::query()->create([
                    'organization_id' => $indicator->organization_id,
                    'indicator_id' => $indicator->id,
                    'value' => $baseline['value'],
                    'baseline_date' => $baseline['baseline_date'] ?? null,
                    'notes' => $baseline['notes'] ?? null,
                ]);
            }
        }

        if (array_key_exists('targets', $data)) {
            $indicator->targets()->delete();
            foreach ($data['targets'] ?? [] as $target) {
                IndicatorTarget::query()->create([
                    'organization_id' => $indicator->organization_id,
                    'indicator_id' => $indicator->id,
                    'value' => $target['value'],
                    'target_period' => $target['target_period'] ?? null,
                    'starts_on' => $target['starts_on'] ?? null,
                    'ends_on' => $target['ends_on'] ?? null,
                    'is_cumulative' => $target['is_cumulative'] ?? false,
                    'notes' => $target['notes'] ?? null,
                ]);
            }
        }

        if (array_key_exists('disaggregations', $data)) {
            $indicator->disaggregations()->delete();
            foreach ($data['disaggregations'] ?? [] as $disaggregation) {
                IndicatorDisaggregation::query()->create([
                    'organization_id' => $indicator->organization_id,
                    'indicator_id' => $indicator->id,
                    'dimension' => $disaggregation['dimension'],
                    'label' => $disaggregation['label'] ?? null,
                    'is_required' => $disaggregation['is_required'] ?? false,
                ]);
            }
        }
    }

    protected function load(Indicator $indicator): Indicator
    {
        return $indicator->load(['baselines', 'targets', 'disaggregations', 'approvalHistories']);
    }

    protected function assertOrg(Indicator $indicator): void
    {
        if ($indicator->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
