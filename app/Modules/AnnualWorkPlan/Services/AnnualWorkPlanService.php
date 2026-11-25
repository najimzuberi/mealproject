<?php

namespace App\Modules\AnnualWorkPlan\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\StrategicPlanStatus;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\AnnualWorkPlan\Models\AwpActivity;
use App\Modules\AnnualWorkPlan\Models\AwpBudgetLine;
use App\Modules\AnnualWorkPlan\Models\AwpMilestone;
use App\Modules\AnnualWorkPlan\Models\AwpPlanMilestone;
use App\Modules\AnnualWorkPlan\Models\AwpPriority;
use App\Modules\Audit\Services\AuditService;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnualWorkPlanService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AnnualWorkPlan
    {
        $this->assertStrategicPlanLink($data['strategic_plan_id']);
        $priorities = $data['priorities'] ?? [];
        $planMilestones = $data['plan_milestones'] ?? [];
        $budgetLines = $data['budget_lines'] ?? [];
        unset($data['priorities'], $data['plan_milestones'], $data['budget_lines']);

        foreach ($priorities as $priority) {
            $this->assertObjectiveOnPlan($data['strategic_plan_id'], (int) $priority['strategic_objective_id']);
        }

        return DB::transaction(function () use ($data, $priorities, $planMilestones, $budgetLines) {
            $organizationId = $this->organizationContext->id();

            $awp = AnnualWorkPlan::query()->create([
                ...$data,
                'organization_id' => $organizationId,
                'status' => StrategicPlanStatus::Draft->value,
                'version' => $data['version'] ?? 1,
                'currency' => $data['currency'] ?? 'USD',
                'total_budget' => $data['total_budget'] ?? 0,
                'created_by' => Auth::id(),
            ]);

            $this->syncPriorities($awp, $priorities);
            $this->syncPlanMilestones($awp, $planMilestones);

            if ($budgetLines !== []) {
                $this->syncBudgetLines($awp, $budgetLines);
            }

            $this->auditService->log(AuditAction::Created, 'annual_work_plans', AnnualWorkPlan::class, $awp->id);

            return $this->loadFullStructure($awp->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AnnualWorkPlan $awp, array $data): AnnualWorkPlan
    {
        $this->assertOrg($awp);
        $this->workflowService->assertEditable($awp, [
            StrategicPlanStatus::Draft->value,
            StrategicPlanStatus::Returned->value,
        ]);

        if (isset($data['strategic_plan_id'])) {
            $this->assertStrategicPlanLink((int) $data['strategic_plan_id']);
        }

        $planId = (int) ($data['strategic_plan_id'] ?? $awp->strategic_plan_id);
        $priorities = $data['priorities'] ?? null;
        unset($data['priorities']);

        if ($priorities !== null) {
            foreach ($priorities as $priority) {
                $this->assertObjectiveOnPlan($planId, (int) $priority['strategic_objective_id']);
            }
        }

        return DB::transaction(function () use ($awp, $data, $priorities) {
            if ($data !== []) {
                $awp->fill($data)->save();
            }

            if ($priorities !== null) {
                $this->clearPriorities($awp);
                $this->syncPriorities($awp, $priorities);
            }

            $this->auditService->log(AuditAction::Updated, 'annual_work_plans', AnnualWorkPlan::class, $awp->id);

            return $this->loadFullStructure($awp->fresh());
        });
    }

    public function delete(AnnualWorkPlan $awp): void
    {
        $this->assertOrg($awp);

        $status = $awp->status instanceof StrategicPlanStatus
            ? $awp->status->value
            : (string) $awp->status;

        if ($status !== StrategicPlanStatus::Draft->value) {
            throw ValidationException::withMessages([
                'status' => ['Only draft annual work plans can be deleted.'],
            ]);
        }

        $awp->delete();
        $this->auditService->log(AuditAction::Deleted, 'annual_work_plans', AnnualWorkPlan::class, $awp->id);
    }

    public function submit(AnnualWorkPlan $awp): AnnualWorkPlan
    {
        $this->assertOrg($awp);
        $this->assertReadyForSubmission($awp);

        $transitioned = $this->workflowService->transition(
            $awp,
            StrategicPlanStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [StrategicPlanStatus::Draft->value, StrategicPlanStatus::Returned->value],
            auditModule: 'annual_work_plans',
        );

        $transitioned->submitted_at = now();
        $transitioned->save();

        return $this->loadFullStructure($transitioned);
    }

    public function returnForCorrection(AnnualWorkPlan $awp, string $comment): AnnualWorkPlan
    {
        $this->assertOrg($awp);

        return $this->loadFullStructure($this->workflowService->transition(
            $awp,
            StrategicPlanStatus::Returned->value,
            ApprovalAction::Returned,
            [StrategicPlanStatus::UnderReview->value],
            $comment,
            auditModule: 'annual_work_plans',
            requireComment: true,
        ));
    }

    public function approve(AnnualWorkPlan $awp, ?string $comment = null): AnnualWorkPlan
    {
        $this->assertOrg($awp);

        return $this->loadFullStructure($this->workflowService->transition(
            $awp,
            StrategicPlanStatus::Approved->value,
            ApprovalAction::Approved,
            [StrategicPlanStatus::UnderReview->value],
            $comment,
            auditModule: 'annual_work_plans',
            setApprovedMeta: true,
        ));
    }

    public function activate(AnnualWorkPlan $awp): AnnualWorkPlan
    {
        $this->assertOrg($awp);

        return $this->loadFullStructure($this->workflowService->transition(
            $awp,
            StrategicPlanStatus::Active->value,
            ApprovalAction::Activated,
            [StrategicPlanStatus::Approved->value],
            auditModule: 'annual_work_plans',
        ));
    }

    public function archive(AnnualWorkPlan $awp): AnnualWorkPlan
    {
        $this->assertOrg($awp);

        return $this->loadFullStructure($this->workflowService->transition(
            $awp,
            StrategicPlanStatus::Archived->value,
            ApprovalAction::Archived,
            [StrategicPlanStatus::Active->value, StrategicPlanStatus::Approved->value, StrategicPlanStatus::Completed->value],
            auditModule: 'annual_work_plans',
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function syncBudgetLines(AnnualWorkPlan $awp, array $lines): AnnualWorkPlan
    {
        $this->assertOrg($awp);
        $this->workflowService->assertEditable($awp, [
            StrategicPlanStatus::Draft->value,
            StrategicPlanStatus::Returned->value,
        ]);

        return DB::transaction(function () use ($awp, $lines) {
            $awp->budgetLines()->delete();

            $currency = $awp->currency ?: 'USD';
            $total = '0.00';

            foreach ($lines as $line) {
                $lineCurrency = $line['currency'] ?? $currency;
                if ($lineCurrency !== $currency && $awp->currency) {
                    throw ValidationException::withMessages([
                        'budget_lines' => ['All budget lines must use the AWP currency.'],
                    ]);
                }

                $amount = number_format((float) $line['amount'], 2, '.', '');

                AwpBudgetLine::query()->create([
                    'organization_id' => $awp->organization_id,
                    'annual_work_plan_id' => $awp->id,
                    'awp_activity_id' => $line['awp_activity_id'],
                    'department_id' => $line['department_id'] ?? null,
                    'code' => $line['code'] ?? null,
                    'category' => $line['category'] ?? null,
                    'description' => $line['description'] ?? null,
                    'amount' => $amount,
                    'currency' => $lineCurrency,
                    'funding_source' => $line['funding_source'] ?? null,
                    'financial_year' => $line['financial_year'] ?? $awp->planning_year,
                    'quarter' => $line['quarter'] ?? null,
                ]);

                $total = bcadd($total, $amount, 2);
            }

            $awp->currency = $currency;
            $awp->total_budget = $total;
            $awp->save();

            $this->recalculateActivityBudgets($awp);

            $this->auditService->log(AuditAction::Updated, 'annual_work_plans', AnnualWorkPlan::class, $awp->id);

            return $this->loadFullStructure($awp->fresh());
        });
    }

    public function budgetSummary(AnnualWorkPlan $awp): array
    {
        $this->assertOrg($awp);
        $awp->load(['budgetLines', 'priorities.activities']);

        $byQuarter = [];
        $byDepartment = [];
        $byActivity = [];

        foreach ($awp->budgetLines as $line) {
            $amount = (string) $line->amount;
            $quarter = $line->quarter ?: 'unassigned';
            $department = (string) ($line->department_id ?: 'unassigned');
            $activity = (string) $line->awp_activity_id;

            $byQuarter[$quarter] = bcadd($byQuarter[$quarter] ?? '0.00', $amount, 2);
            $byDepartment[$department] = bcadd($byDepartment[$department] ?? '0.00', $amount, 2);
            $byActivity[$activity] = bcadd($byActivity[$activity] ?? '0.00', $amount, 2);
        }

        return [
            'currency' => $awp->currency,
            'total_budget' => (string) $awp->total_budget,
            'by_quarter' => $byQuarter,
            'by_department' => $byDepartment,
            'by_activity' => $byActivity,
        ];
    }

    public function loadFullStructure(AnnualWorkPlan $awp): AnnualWorkPlan
    {
        return $awp->load([
            'priorities.activities.milestones',
            'priorities.activities.budgetLines',
            'priorities.activities.objectives',
            'planMilestones',
            'budgetLines',
            'strategicPlan',
            'owner',
            'department',
            'approvalHistories',
        ]);
    }

    protected function recalculateActivityBudgets(AnnualWorkPlan $awp): void
    {
        $totals = AwpBudgetLine::query()
            ->where('annual_work_plan_id', $awp->id)
            ->selectRaw('awp_activity_id, SUM(amount) as total')
            ->groupBy('awp_activity_id')
            ->pluck('total', 'awp_activity_id');

        foreach ($totals as $activityId => $total) {
            AwpActivity::query()
                ->where('id', $activityId)
                ->where('organization_id', $awp->organization_id)
                ->update(['budget_estimate' => $total]);
        }
    }

    protected function assertReadyForSubmission(AnnualWorkPlan $awp): void
    {
        $awp->loadMissing(['priorities.activities']);

        if ($awp->priorities->isEmpty()) {
            throw ValidationException::withMessages([
                'priorities' => ['At least one annual priority is required before submission.'],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $priorities
     */
    protected function syncPriorities(AnnualWorkPlan $awp, array $priorities): void
    {
        $organizationId = $awp->organization_id;

        foreach ($priorities as $index => $priorityData) {
            $priority = AwpPriority::query()->create([
                'organization_id' => $organizationId,
                'annual_work_plan_id' => $awp->id,
                'strategic_objective_id' => $priorityData['strategic_objective_id'],
                'code' => $priorityData['code'] ?? null,
                'department_id' => $priorityData['department_id'] ?? null,
                'title' => $priorityData['title'],
                'description' => $priorityData['description'] ?? null,
                'priority_level' => $priorityData['priority_level'] ?? 0,
                'status' => $priorityData['status'] ?? 'active',
                'sort_order' => $priorityData['sort_order'] ?? $index,
            ]);

            foreach ($priorityData['activities'] ?? [] as $activityIndex => $activityData) {
                $activity = AwpActivity::query()->create([
                    'organization_id' => $organizationId,
                    'annual_work_plan_id' => $awp->id,
                    'awp_priority_id' => $priority->id,
                    'code' => $activityData['code'] ?? null,
                    'department_id' => $activityData['department_id'] ?? null,
                    'responsible_user_id' => $activityData['responsible_user_id'] ?? null,
                    'team_id' => $activityData['team_id'] ?? null,
                    'geographic_unit_id' => $activityData['geographic_unit_id'] ?? null,
                    'strategic_outcome_id' => $activityData['strategic_outcome_id'] ?? null,
                    'title' => $activityData['title'],
                    'description' => $activityData['description'] ?? null,
                    'deliverable' => $activityData['deliverable'] ?? null,
                    'expected_output' => $activityData['expected_output'] ?? null,
                    'budget_estimate' => $activityData['budget_estimate'] ?? null,
                    'funding_source' => $activityData['funding_source'] ?? null,
                    'starts_on' => $activityData['starts_on'] ?? null,
                    'ends_on' => $activityData['ends_on'] ?? null,
                    'quarter' => $activityData['quarter'] ?? null,
                    'status' => $activityData['status'] ?? 'planned',
                ]);

                if (! empty($activityData['objective_ids'])) {
                    $activity->objectives()->sync($activityData['objective_ids']);
                } else {
                    $activity->objectives()->sync([$priority->strategic_objective_id]);
                }

                foreach ($activityData['milestones'] ?? [] as $milestoneData) {
                    AwpMilestone::query()->create([
                        'organization_id' => $organizationId,
                        'awp_activity_id' => $activity->id,
                        'quarter' => $milestoneData['quarter'],
                        'title' => $milestoneData['title'],
                        'due_on' => $milestoneData['due_on'] ?? null,
                        'status' => $milestoneData['status'] ?? 'pending',
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $milestones
     */
    protected function syncPlanMilestones(AnnualWorkPlan $awp, array $milestones): void
    {
        foreach ($milestones as $milestone) {
            AwpPlanMilestone::query()->create([
                'organization_id' => $awp->organization_id,
                'annual_work_plan_id' => $awp->id,
                'strategic_objective_id' => $milestone['strategic_objective_id'] ?? null,
                'department_id' => $milestone['department_id'] ?? null,
                'team_id' => $milestone['team_id'] ?? null,
                'code' => $milestone['code'] ?? null,
                'title' => $milestone['title'],
                'description' => $milestone['description'] ?? null,
                'quarter' => $milestone['quarter'],
                'start_date' => $milestone['start_date'] ?? null,
                'end_date' => $milestone['end_date'] ?? null,
                'expected_result' => $milestone['expected_result'] ?? null,
                'status' => $milestone['status'] ?? 'pending',
            ]);
        }
    }

    protected function clearPriorities(AnnualWorkPlan $awp): void
    {
        $awp->load(['priorities.activities.milestones']);

        foreach ($awp->priorities as $priority) {
            foreach ($priority->activities as $activity) {
                $activity->milestones()->delete();
                $activity->delete();
            }
            $priority->delete();
        }
    }

    protected function assertStrategicPlanLink(int $strategicPlanId): void
    {
        $organizationId = $this->organizationContext->id();

        $exists = StrategicPlan::query()
            ->where('id', $strategicPlanId)
            ->where('organization_id', $organizationId)
            ->whereIn('status', [
                StrategicPlanStatus::Approved->value,
                StrategicPlanStatus::Active->value,
            ])
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'strategic_plan_id' => ['Strategic plan must be approved or active in this organization.'],
            ]);
        }
    }

    protected function assertObjectiveOnPlan(int $strategicPlanId, int $objectiveId): void
    {
        $organizationId = $this->organizationContext->id();

        $valid = StrategicObjective::query()
            ->where('id', $objectiveId)
            ->where('organization_id', $organizationId)
            ->whereHas('strategicGoal', fn ($query) => $query->where('strategic_plan_id', $strategicPlanId))
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'priorities' => ['Each priority must reference a strategic objective on the selected strategic plan.'],
            ]);
        }
    }

    protected function assertOrg(AnnualWorkPlan $awp): void
    {
        if ($awp->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
