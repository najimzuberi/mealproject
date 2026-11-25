<?php

namespace App\Modules\MonitoringPlan\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\IndicatorStatus;
use App\Enums\MonitoringPlanStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\DataCollection\Services\AssignmentGenerationService;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonitoringPlanService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
        protected AssignmentGenerationService $assignmentGenerationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MonitoringPlan
    {
        return DB::transaction(function () use ($data) {
            $project = Project::query()
                ->where('organization_id', $this->organizationContext->id())
                ->where('id', $data['project_id'])
                ->firstOrFail();

            $plan = MonitoringPlan::query()->create([
                'organization_id' => $project->organization_id,
                'code' => $data['code'],
                'project_id' => $project->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => MonitoringPlanStatus::Draft->value,
                'version' => 1,
                'intended_use' => $data['intended_use'] ?? null,
                'assumptions' => $data['assumptions'] ?? null,
                'limitations' => $data['limitations'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($plan, $data['items'] ?? []);
            $this->auditService->log(AuditAction::Created, 'monitoring_plans', MonitoringPlan::class, $plan->id);

            return $this->load($plan);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MonitoringPlan $plan, array $data): MonitoringPlan
    {
        $this->assertOrg($plan);

        $status = $plan->status instanceof MonitoringPlanStatus ? $plan->status->value : (string) $plan->status;

        if (in_array($status, [
            MonitoringPlanStatus::Approved->value,
            MonitoringPlanStatus::Active->value,
        ], true)) {
            return $this->createVersion($plan, $data);
        }

        $this->workflowService->assertEditable($plan, [
            MonitoringPlanStatus::Draft->value,
            MonitoringPlanStatus::Returned->value,
            MonitoringPlanStatus::UnderReview->value,
        ]);

        return DB::transaction(function () use ($plan, $data) {
            $plan->fill(collect($data)->only([
                'code', 'title', 'description', 'intended_use', 'assumptions', 'limitations',
            ])->all())->save();

            if (array_key_exists('items', $data)) {
                $this->syncItems($plan, $data['items'] ?? []);
            }

            $this->auditService->log(AuditAction::Updated, 'monitoring_plans', MonitoringPlan::class, $plan->id);

            return $this->load($plan->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(MonitoringPlan $plan, array $data = []): MonitoringPlan
    {
        $this->assertOrg($plan);

        return DB::transaction(function () use ($plan, $data) {
            $new = MonitoringPlan::query()->create([
                'organization_id' => $plan->organization_id,
                'code' => $data['code'] ?? $plan->code,
                'project_id' => $plan->project_id,
                'title' => $data['title'] ?? $plan->title,
                'description' => $data['description'] ?? $plan->description,
                'status' => MonitoringPlanStatus::Draft->value,
                'version' => $plan->version + 1,
                'parent_monitoring_plan_id' => $plan->parent_monitoring_plan_id ?? $plan->id,
                'intended_use' => $data['intended_use'] ?? $plan->intended_use,
                'assumptions' => $data['assumptions'] ?? $plan->assumptions,
                'limitations' => $data['limitations'] ?? $plan->limitations,
                'created_by' => Auth::id(),
            ]);

            $status = $plan->status instanceof MonitoringPlanStatus ? $plan->status->value : (string) $plan->status;
            if (in_array($status, [MonitoringPlanStatus::Approved->value, MonitoringPlanStatus::Active->value], true)) {
                $plan->status = MonitoringPlanStatus::Superseded->value;
                $plan->save();
            }

            $items = $data['items'] ?? $plan->items->map(fn (MonitoringPlanItem $item) => $item->only([
                'indicator_id', 'baseline_value', 'target_value', 'data_source', 'collection_method',
                'collection_frequency', 'collection_deadline', 'responsible_collector_id', 'supervisor_id',
                'me_validator_id', 'means_of_verification', 'validation_rules', 'reporting_format',
                'intended_use', 'assumptions', 'limitations', 'sort_order',
            ]))->all();

            $this->syncItems($new, $items);
            $this->auditService->log(AuditAction::Created, 'monitoring_plans', MonitoringPlan::class, $new->id, null, [
                'versioned_from' => $plan->id,
            ]);

            return $this->load($new);
        });
    }

    public function submit(MonitoringPlan $plan, ?string $comment = null): MonitoringPlan
    {
        $this->assertOrg($plan);

        return $this->load($this->workflowService->transition(
            $plan,
            MonitoringPlanStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [MonitoringPlanStatus::Draft->value, MonitoringPlanStatus::Returned->value],
            $comment,
            'monitoring_plans',
        ));
    }

    public function returnForCorrection(MonitoringPlan $plan, ?string $comment = null): MonitoringPlan
    {
        $this->assertOrg($plan);

        return $this->load($this->workflowService->transition(
            $plan,
            MonitoringPlanStatus::Returned->value,
            ApprovalAction::Returned,
            [MonitoringPlanStatus::UnderReview->value],
            $comment,
            'monitoring_plans',
            true,
        ));
    }

    public function approve(MonitoringPlan $plan, ?string $comment = null): MonitoringPlan
    {
        $this->assertOrg($plan);

        return $this->load($this->workflowService->transition(
            $plan,
            MonitoringPlanStatus::Approved->value,
            ApprovalAction::Approved,
            [MonitoringPlanStatus::UnderReview->value],
            $comment,
            'monitoring_plans',
            false,
            true,
        ));
    }

    public function activate(MonitoringPlan $plan, ?string $comment = null): MonitoringPlan
    {
        $this->assertOrg($plan);

        $activated = $this->workflowService->transition(
            $plan,
            MonitoringPlanStatus::Active->value,
            ApprovalAction::Activated,
            [MonitoringPlanStatus::Approved->value],
            $comment,
            'monitoring_plans',
        );

        $this->assignmentGenerationService->generateFromMonitoringPlan($activated);

        return $this->load($activated);
    }

    public function archive(MonitoringPlan $plan, ?string $comment = null): MonitoringPlan
    {
        $this->assertOrg($plan);

        return $this->load($this->workflowService->transition(
            $plan,
            MonitoringPlanStatus::Archived->value,
            ApprovalAction::Archived,
            [
                MonitoringPlanStatus::Approved->value,
                MonitoringPlanStatus::Active->value,
                MonitoringPlanStatus::Superseded->value,
            ],
            $comment,
            'monitoring_plans',
        ));
    }

    /**
     * @return list<MonitoringPlan>
     */
    public function versionHistory(MonitoringPlan $plan): array
    {
        $this->assertOrg($plan);
        $rootId = $plan->parent_monitoring_plan_id ?? $plan->id;

        return MonitoringPlan::query()
            ->where('organization_id', $plan->organization_id)
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_monitoring_plan_id', $rootId);
            })
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function syncItems(MonitoringPlan $plan, array $items): void
    {
        $plan->items()->forceDelete();

        foreach ($items as $index => $item) {
            $indicator = Indicator::query()
                ->where('organization_id', $plan->organization_id)
                ->where('id', $item['indicator_id'])
                ->first();

            if (! $indicator) {
                throw ValidationException::withMessages([
                    'items' => ['Indicator not found in this organization.'],
                ]);
            }

            $status = $indicator->status instanceof IndicatorStatus ? $indicator->status->value : (string) $indicator->status;
            if ($status !== IndicatorStatus::Approved->value) {
                throw ValidationException::withMessages([
                    'items' => ['Monitoring plan items must reference approved indicators.'],
                ]);
            }

            MonitoringPlanItem::query()->create([
                'organization_id' => $plan->organization_id,
                'monitoring_plan_id' => $plan->id,
                'indicator_id' => $indicator->id,
                'baseline_value' => $item['baseline_value'] ?? null,
                'target_value' => $item['target_value'] ?? null,
                'data_source' => $item['data_source'] ?? $indicator->data_source,
                'collection_method' => $item['collection_method'] ?? $indicator->collection_method,
                'collection_frequency' => $item['collection_frequency'] ?? $indicator->collection_frequency,
                'collection_deadline' => $item['collection_deadline'] ?? null,
                'responsible_collector_id' => $item['responsible_collector_id'] ?? $indicator->responsible_collector_id,
                'supervisor_id' => $item['supervisor_id'] ?? $indicator->supervisor_id,
                'me_validator_id' => $item['me_validator_id'] ?? $indicator->me_validator_id,
                'means_of_verification' => $item['means_of_verification'] ?? $indicator->means_of_verification,
                'validation_rules' => $item['validation_rules'] ?? $indicator->validation_rules,
                'reporting_format' => $item['reporting_format'] ?? null,
                'intended_use' => $item['intended_use'] ?? null,
                'assumptions' => $item['assumptions'] ?? null,
                'limitations' => $item['limitations'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    protected function load(MonitoringPlan $plan): MonitoringPlan
    {
        return $plan->load(['project', 'items.indicator', 'approvalHistories']);
    }

    protected function assertOrg(MonitoringPlan $plan): void
    {
        if ($plan->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
