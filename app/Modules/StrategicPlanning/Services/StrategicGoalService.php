<?php

namespace App\Modules\StrategicPlanning\Services;

use App\Enums\AuditAction;
use App\Enums\StrategicPlanStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\StrategicPlanning\Models\KeyResultArea;
use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicIndicator;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicOutcome;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StrategicGoalService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @return Collection<int, StrategicGoal>
     */
    public function listForPlan(StrategicPlan $plan): Collection
    {
        $this->assertPlanOrg($plan);

        return StrategicGoal::query()
            ->where('strategic_plan_id', $plan->id)
            ->where('organization_id', $this->organizationContext->id())
            ->with(['departments', 'objectives.departments', 'objectives.responsibleUsers', 'objectives.keyResultAreas', 'objectives.outcomes', 'objectives.indicators'])
            ->orderBy('priority')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createGoal(StrategicPlan $plan, array $data): StrategicGoal
    {
        $this->assertPlanOrg($plan);
        $this->assertPlanEditable($plan);

        return DB::transaction(function () use ($plan, $data) {
            $goal = StrategicGoal::query()->create([
                'organization_id' => $plan->organization_id,
                'strategic_plan_id' => $plan->id,
                'strategic_theme_id' => $data['strategic_theme_id'] ?? null,
                'code' => $data['code'] ?? null,
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? 0,
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'description' => $data['description'] ?? null,
                'expected_result' => $data['expected_result'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['department_ids'])) {
                $goal->departments()->sync($data['department_ids']);
            }

            foreach ($data['objectives'] ?? [] as $objectiveData) {
                $this->createObjective($goal, $objectiveData);
            }

            $this->auditService->log(AuditAction::Created, 'strategic_plans', StrategicGoal::class, $goal->id);

            return $this->loadGoal($goal);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGoal(StrategicGoal $goal, array $data): StrategicGoal
    {
        $this->assertGoalOrg($goal);
        $this->assertPlanEditable($goal->strategicPlan);

        return DB::transaction(function () use ($goal, $data) {
            $departmentIds = $data['department_ids'] ?? null;
            unset($data['department_ids'], $data['objectives']);

            $goal->fill($data)->save();

            if ($departmentIds !== null) {
                $goal->departments()->sync($departmentIds);
            }

            $this->auditService->log(AuditAction::Updated, 'strategic_plans', StrategicGoal::class, $goal->id);

            return $this->loadGoal($goal->fresh());
        });
    }

    public function deleteGoal(StrategicPlan $plan, StrategicGoal $goal): void
    {
        $this->assertPlanOrg($plan);
        $this->assertGoalOrg($goal);

        if ((int) $goal->strategic_plan_id !== (int) $plan->id) {
            abort(404);
        }

        $this->assertPlanEditable($plan);

        if ($goal->objectives()->where('status', 'approved')->exists()) {
            throw ValidationException::withMessages([
                'goal' => ['Cannot delete a goal that has approved dependent objectives.'],
            ]);
        }

        DB::transaction(function () use ($goal): void {
            $goal->load(['objectives.keyResultAreas', 'objectives.outcomes.indicators', 'objectives.indicators']);

            foreach ($goal->objectives as $objective) {
                $objective->departments()->detach();
                $objective->responsibleUsers()->detach();
                $objective->keyResultAreas()->delete();
                foreach ($objective->outcomes as $outcome) {
                    $outcome->indicators()->delete();
                    $outcome->delete();
                }
                $objective->indicators()->delete();
                $objective->delete();
            }

            $goal->departments()->detach();
            $goal->delete();
        });

        $this->auditService->log(AuditAction::Deleted, 'strategic_plans', StrategicGoal::class, $goal->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createObjective(StrategicGoal $goal, array $data): StrategicObjective
    {
        $this->assertGoalOrg($goal);
        $this->assertPlanEditable($goal->strategicPlan);

        return DB::transaction(function () use ($goal, $data) {
            $objective = StrategicObjective::query()->create([
                'organization_id' => $goal->organization_id,
                'strategic_goal_id' => $goal->id,
                'code' => $data['code'] ?? null,
                'title' => $data['title'],
                'priority' => $data['priority'] ?? 0,
                'description' => $data['description'] ?? null,
                'expected_result' => $data['expected_result'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['department_ids'])) {
                $objective->departments()->sync($data['department_ids']);
            }

            if (! empty($data['responsible_user_ids'])) {
                $objective->responsibleUsers()->sync($data['responsible_user_ids']);
            }

            foreach ($data['kra'] ?? [] as $index => $kraData) {
                KeyResultArea::query()->create([
                    'organization_id' => $goal->organization_id,
                    'strategic_objective_id' => $objective->id,
                    'department_id' => $kraData['department_id'] ?? null,
                    'code' => $kraData['code'] ?? null,
                    'title' => $kraData['title'],
                    'description' => $kraData['description'] ?? null,
                    'priority' => $kraData['priority'] ?? 0,
                    'status' => $kraData['status'] ?? 'active',
                    'sort_order' => $kraData['sort_order'] ?? $index,
                ]);
            }

            foreach ($data['outcomes'] ?? [] as $index => $outcomeData) {
                $outcome = StrategicOutcome::query()->create([
                    'organization_id' => $goal->organization_id,
                    'strategic_plan_id' => $goal->strategic_plan_id,
                    'strategic_objective_id' => $objective->id,
                    'department_id' => $outcomeData['department_id'] ?? null,
                    'code' => $outcomeData['code'] ?? null,
                    'title' => $outcomeData['title'],
                    'description' => $outcomeData['description'] ?? null,
                    'expected_change' => $outcomeData['expected_change'] ?? null,
                    'target_period' => $outcomeData['target_period'] ?? null,
                    'status' => $outcomeData['status'] ?? 'active',
                    'sort_order' => $outcomeData['sort_order'] ?? $index,
                ]);

                foreach ($outcomeData['indicators'] ?? [] as $indicatorData) {
                    $this->createIndicator($goal, $objective, $indicatorData, $outcome->id);
                }
            }

            foreach ($data['indicators'] ?? [] as $indicatorData) {
                $this->createIndicator($goal, $objective, $indicatorData);
            }

            $this->auditService->log(AuditAction::Created, 'strategic_plans', StrategicObjective::class, $objective->id);

            return $this->loadObjective($objective);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateObjective(StrategicObjective $objective, array $data): StrategicObjective
    {
        $this->assertObjectiveOrg($objective);
        $this->assertPlanEditable($objective->strategicGoal->strategicPlan);

        return DB::transaction(function () use ($objective, $data) {
            $departmentIds = $data['department_ids'] ?? null;
            $userIds = $data['responsible_user_ids'] ?? null;
            unset($data['department_ids'], $data['responsible_user_ids'], $data['kra'], $data['outcomes'], $data['indicators']);

            $objective->fill($data)->save();

            if ($departmentIds !== null) {
                $objective->departments()->sync($departmentIds);
            }

            if ($userIds !== null) {
                $objective->responsibleUsers()->sync($userIds);
            }

            $this->auditService->log(AuditAction::Updated, 'strategic_plans', StrategicObjective::class, $objective->id);

            return $this->loadObjective($objective->fresh());
        });
    }

    public function loadGoal(StrategicGoal $goal): StrategicGoal
    {
        return $goal->load([
            'departments',
            'objectives.departments',
            'objectives.responsibleUsers',
            'objectives.keyResultAreas',
            'objectives.outcomes.indicators',
            'objectives.indicators',
        ]);
    }

    public function loadObjective(StrategicObjective $objective): StrategicObjective
    {
        return $objective->load([
            'departments',
            'responsibleUsers',
            'keyResultAreas',
            'outcomes.indicators',
            'indicators',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createIndicator(
        StrategicGoal $goal,
        StrategicObjective $objective,
        array $data,
        ?int $outcomeId = null,
    ): StrategicIndicator {
        return StrategicIndicator::query()->create([
            'organization_id' => $goal->organization_id,
            'strategic_plan_id' => $goal->strategic_plan_id,
            'strategic_objective_id' => $objective->id,
            'strategic_outcome_id' => $outcomeId,
            'department_id' => $data['department_id'] ?? null,
            'indicator_id' => $data['indicator_id'] ?? null,
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'definition' => $data['definition'] ?? null,
            'measurement_unit' => $data['measurement_unit'] ?? null,
            'measurement_direction' => $data['measurement_direction'] ?? null,
            'baseline_value' => $data['baseline_value'] ?? null,
            'baseline_date' => $data['baseline_date'] ?? null,
            'target_value' => $data['target_value'] ?? null,
            'target_period' => $data['target_period'] ?? null,
            'data_source' => $data['data_source'] ?? null,
            'measurement_frequency' => $data['measurement_frequency'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    protected function assertPlanEditable(StrategicPlan $plan): void
    {
        $status = $plan->status instanceof StrategicPlanStatus
            ? $plan->status
            : StrategicPlanStatus::from((string) $plan->status);

        if (! in_array($status, [StrategicPlanStatus::Draft, StrategicPlanStatus::Returned, StrategicPlanStatus::Approved, StrategicPlanStatus::Active], true)) {
            throw ValidationException::withMessages([
                'status' => ["Goals cannot be modified while the plan is [{$status->value}]."],
            ]);
        }

        if (in_array($status, [StrategicPlanStatus::Approved, StrategicPlanStatus::Active], true)) {
            // Controlled amendment: allow additive goal/objective work on approved plans.
            return;
        }
    }

    protected function assertPlanOrg(StrategicPlan $plan): void
    {
        if ($plan->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }

    protected function assertGoalOrg(StrategicGoal $goal): void
    {
        if ($goal->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }

    protected function assertObjectiveOrg(StrategicObjective $objective): void
    {
        if ($objective->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
