<?php

namespace App\Modules\StrategicPlanning\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\StrategicPlanStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Documents\Models\Document;
use App\Modules\StrategicPlanning\Models\KeyResultArea;
use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicIndicator;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicOutcome;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Modules\StrategicPlanning\Models\StrategicPlanPriority;
use App\Modules\StrategicPlanning\Models\StrategicTheme;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StrategicPlanService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StrategicPlan
    {
        return DB::transaction(function () use ($data) {
            $organizationId = $this->organizationContext->id();
            $themes = $data['themes'] ?? [];
            $goals = $data['goals'] ?? [];
            $priorities = $data['priorities'] ?? [];
            unset($data['themes'], $data['goals'], $data['priorities']);

            $plan = StrategicPlan::query()->create([
                ...$data,
                'organization_id' => $organizationId,
                'status' => StrategicPlanStatus::Draft->value,
                'version' => $data['version'] ?? 1,
                'created_by' => Auth::id(),
            ]);

            $this->syncNestedStructure($plan, $themes, $goals);
            $this->syncPriorities($plan, $priorities);

            $this->auditService->log(AuditAction::Created, 'strategic_plans', StrategicPlan::class, $plan->id);

            return $this->loadFullStructure($plan);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StrategicPlan $plan, array $data): StrategicPlan
    {
        $this->assertOrg($plan);

        $status = $plan->status instanceof StrategicPlanStatus
            ? $plan->status
            : StrategicPlanStatus::from((string) $plan->status);

        if ($status === StrategicPlanStatus::Archived || $status === StrategicPlanStatus::UnderReview) {
            throw ValidationException::withMessages([
                'status' => ["Record in status [{$status->value}] cannot be modified."],
            ]);
        }

        if (in_array($status, [StrategicPlanStatus::Approved, StrategicPlanStatus::Active], true)) {
            return $this->createNewVersion($plan, $data);
        }

        return DB::transaction(function () use ($plan, $data) {
            $this->workflowService->assertEditable($plan, [
                StrategicPlanStatus::Draft->value,
                StrategicPlanStatus::Returned->value,
            ]);

            $themes = $data['themes'] ?? null;
            $goals = $data['goals'] ?? null;
            $priorities = $data['priorities'] ?? null;
            unset($data['themes'], $data['goals'], $data['priorities']);

            if ($data !== []) {
                $plan->fill($data)->save();
            }

            if ($themes !== null || $goals !== null) {
                $this->clearNestedStructure($plan);
                $this->syncNestedStructure($plan, $themes ?? [], $goals ?? []);
            }

            if ($priorities !== null) {
                $this->clearPriorities($plan);
                $this->syncPriorities($plan, $priorities);
            }

            $this->auditService->log(AuditAction::Updated, 'strategic_plans', StrategicPlan::class, $plan->id);

            return $this->loadFullStructure($plan->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNewVersion(StrategicPlan $plan, array $data): StrategicPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $newVersion = ($plan->version ?? 1) + 1;
            $code = $data['code'] ?? $plan->code.'-v'.$newVersion;

            $themes = $data['themes'] ?? null;
            $goals = $data['goals'] ?? null;
            $priorities = $data['priorities'] ?? null;
            unset($data['themes'], $data['goals'], $data['priorities']);

            $draft = StrategicPlan::query()->create([
                'organization_id' => $plan->organization_id,
                'code' => $code,
                'title' => $data['title'] ?? $plan->title,
                'description' => $data['description'] ?? $plan->description,
                'vision' => $data['vision'] ?? $plan->vision,
                'mission' => $data['mission'] ?? $plan->mission,
                'strategic_context' => $data['strategic_context'] ?? $plan->strategic_context,
                'core_values' => $data['core_values'] ?? $plan->core_values,
                'start_date' => $data['start_date'] ?? $plan->start_date,
                'end_date' => $data['end_date'] ?? $plan->end_date,
                'owner_user_id' => $data['owner_user_id'] ?? $plan->owner_user_id,
                'department_id' => $data['department_id'] ?? $plan->department_id,
                'status' => StrategicPlanStatus::Draft->value,
                'version' => $newVersion,
                'parent_plan_id' => $plan->id,
                'created_by' => Auth::id(),
            ]);

            if ($themes !== null || $goals !== null) {
                $this->syncNestedStructure($draft, $themes ?? [], $goals ?? []);
            } else {
                $this->copyNestedStructure($plan, $draft);
            }

            if ($priorities !== null) {
                $this->syncPriorities($draft, $priorities);
            } else {
                $this->copyPriorities($plan, $draft);
            }

            $this->auditService->log(AuditAction::Created, 'strategic_plans', StrategicPlan::class, $draft->id);

            return $this->loadFullStructure($draft);
        });
    }

    public function duplicate(StrategicPlan $plan): StrategicPlan
    {
        $this->assertOrg($plan);

        return DB::transaction(function () use ($plan) {
            $code = $plan->code.'-COPY-'.now()->format('YmdHis');

            $draft = StrategicPlan::query()->create([
                'organization_id' => $plan->organization_id,
                'code' => $code,
                'title' => $plan->title.' (Copy)',
                'description' => $plan->description,
                'vision' => $plan->vision,
                'mission' => $plan->mission,
                'strategic_context' => $plan->strategic_context,
                'core_values' => $plan->core_values,
                'start_date' => $plan->start_date,
                'end_date' => $plan->end_date,
                'owner_user_id' => $plan->owner_user_id,
                'department_id' => $plan->department_id,
                'status' => StrategicPlanStatus::Draft->value,
                'version' => 1,
                'created_by' => Auth::id(),
            ]);

            $this->copyNestedStructure($plan, $draft);
            $this->copyPriorities($plan, $draft);

            $this->auditService->log(AuditAction::Created, 'strategic_plans', StrategicPlan::class, $draft->id);

            return $this->loadFullStructure($draft);
        });
    }

    public function delete(StrategicPlan $plan): void
    {
        $this->assertOrg($plan);

        $status = $plan->status instanceof StrategicPlanStatus
            ? $plan->status->value
            : (string) $plan->status;

        if (! in_array($status, [StrategicPlanStatus::Draft->value, StrategicPlanStatus::Returned->value], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only draft or returned strategic plans can be deleted.'],
            ]);
        }

        $plan->delete();
        $this->auditService->log(AuditAction::Deleted, 'strategic_plans', StrategicPlan::class, $plan->id);
    }

    public function submit(StrategicPlan $plan): StrategicPlan
    {
        $this->assertOrg($plan);

        $transitioned = $this->workflowService->transition(
            $plan,
            StrategicPlanStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [StrategicPlanStatus::Draft->value, StrategicPlanStatus::Returned->value],
            auditModule: 'strategic_plans',
        );

        $transitioned->submitted_at = now();
        $transitioned->save();

        return $this->loadFullStructure($transitioned);
    }

    public function returnForCorrection(StrategicPlan $plan, string $comment): StrategicPlan
    {
        $this->assertOrg($plan);

        return $this->loadFullStructure($this->workflowService->transition(
            $plan,
            StrategicPlanStatus::Returned->value,
            ApprovalAction::Returned,
            [StrategicPlanStatus::UnderReview->value],
            $comment,
            auditModule: 'strategic_plans',
            requireComment: true,
        ));
    }

    public function approve(StrategicPlan $plan, ?string $comment = null): StrategicPlan
    {
        $this->assertOrg($plan);

        return $this->loadFullStructure($this->workflowService->transition(
            $plan,
            StrategicPlanStatus::Approved->value,
            ApprovalAction::Approved,
            [StrategicPlanStatus::UnderReview->value],
            $comment,
            auditModule: 'strategic_plans',
            setApprovedMeta: true,
        ));
    }

    public function activate(StrategicPlan $plan): StrategicPlan
    {
        $this->assertOrg($plan);

        return DB::transaction(function () use ($plan) {
            $activated = $this->workflowService->transition(
                $plan,
                StrategicPlanStatus::Active->value,
                ApprovalAction::Activated,
                [StrategicPlanStatus::Approved->value],
                auditModule: 'strategic_plans',
            );

            StrategicPlan::query()
                ->where('organization_id', $plan->organization_id)
                ->where('id', '!=', $plan->id)
                ->where('status', StrategicPlanStatus::Active->value)
                ->each(function (StrategicPlan $previous): void {
                    $this->workflowService->transition(
                        $previous,
                        StrategicPlanStatus::Archived->value,
                        ApprovalAction::Archived,
                        [StrategicPlanStatus::Active->value],
                        auditModule: 'strategic_plans',
                    );
                });

            return $this->loadFullStructure($activated);
        });
    }

    public function archive(StrategicPlan $plan): StrategicPlan
    {
        $this->assertOrg($plan);

        return $this->loadFullStructure($this->workflowService->transition(
            $plan,
            StrategicPlanStatus::Archived->value,
            ApprovalAction::Archived,
            [StrategicPlanStatus::Active->value, StrategicPlanStatus::Approved->value, StrategicPlanStatus::Completed->value],
            auditModule: 'strategic_plans',
        ));
    }

    /**
     * @return Collection<int, StrategicPlan>
     */
    public function versions(StrategicPlan $plan): Collection
    {
        $this->assertOrg($plan);

        $rootId = $plan->parent_plan_id ?: $plan->id;

        return StrategicPlan::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where(function ($query) use ($rootId, $plan): void {
                $query->where('id', $rootId)
                    ->orWhere('parent_plan_id', $rootId)
                    ->orWhere('id', $plan->id);
            })
            ->orderBy('version')
            ->get();
    }

    public function storeDocument(StrategicPlan $plan, UploadedFile $file, ?string $title = null): Document
    {
        $this->assertOrg($plan);

        $path = $file->store("strategic-plans/{$plan->id}", 'public');

        $document = $plan->documents()->create([
            'organization_id' => $plan->organization_id,
            'title' => $title ?: $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditService->log(AuditAction::Created, 'strategic_plans', Document::class, $document->id);

        return $document;
    }

    public function deleteDocument(StrategicPlan $plan, Document $document): void
    {
        $this->assertOrg($plan);

        if ($document->documentable_type !== StrategicPlan::class || (int) $document->documentable_id !== (int) $plan->id) {
            abort(404);
        }

        if ($document->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        $this->auditService->log(AuditAction::Deleted, 'strategic_plans', Document::class, $document->id);
    }

    public function loadFullStructure(StrategicPlan $plan): StrategicPlan
    {
        return $plan->load([
            'themes',
            'priorities.department',
            'documents',
            'owner',
            'department',
            'creator',
            'approver',
            'approvalHistories',
            'goals.departments',
            'goals.objectives.departments',
            'goals.objectives.responsibleUsers',
            'goals.objectives.keyResultAreas',
            'goals.objectives.outcomes.indicators',
            'goals.objectives.indicators',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $themes
     * @param  list<array<string, mixed>>  $goals
     */
    protected function syncNestedStructure(StrategicPlan $plan, array $themes, array $goals): void
    {
        $organizationId = $plan->organization_id;
        $themeMap = [];

        foreach ($themes as $index => $themeData) {
            $theme = StrategicTheme::query()->create([
                'organization_id' => $organizationId,
                'strategic_plan_id' => $plan->id,
                'name' => $themeData['name'],
                'description' => $themeData['description'] ?? null,
                'sort_order' => $themeData['sort_order'] ?? $index,
            ]);
            $themeMap[$index] = $theme->id;
            if (isset($themeData['id'])) {
                $themeMap[$themeData['id']] = $theme->id;
            }
        }

        foreach ($goals as $index => $goalData) {
            $themeId = $goalData['strategic_theme_id'] ?? null;
            if ($themeId === null && isset($goalData['theme_index'])) {
                $themeId = $themeMap[$goalData['theme_index']] ?? null;
            }

            $goal = StrategicGoal::query()->create([
                'organization_id' => $organizationId,
                'strategic_plan_id' => $plan->id,
                'strategic_theme_id' => $themeId,
                'code' => $goalData['code'] ?? null,
                'title' => $goalData['title'],
                'category' => $goalData['category'] ?? null,
                'priority' => $goalData['priority'] ?? 0,
                'owner_user_id' => $goalData['owner_user_id'] ?? null,
                'description' => $goalData['description'] ?? null,
                'expected_result' => $goalData['expected_result'] ?? null,
                'start_date' => $goalData['start_date'] ?? null,
                'end_date' => $goalData['end_date'] ?? null,
                'status' => $goalData['status'] ?? 'active',
                'sort_order' => $goalData['sort_order'] ?? $index,
            ]);

            if (! empty($goalData['department_ids'])) {
                $goal->departments()->sync($goalData['department_ids']);
            }

            foreach ($goalData['objectives'] ?? [] as $objectiveIndex => $objectiveData) {
                $objective = StrategicObjective::query()->create([
                    'organization_id' => $organizationId,
                    'strategic_goal_id' => $goal->id,
                    'code' => $objectiveData['code'] ?? null,
                    'title' => $objectiveData['title'],
                    'priority' => $objectiveData['priority'] ?? 0,
                    'description' => $objectiveData['description'] ?? null,
                    'expected_result' => $objectiveData['expected_result'] ?? null,
                    'start_date' => $objectiveData['start_date'] ?? null,
                    'end_date' => $objectiveData['end_date'] ?? null,
                    'status' => $objectiveData['status'] ?? 'active',
                    'sort_order' => $objectiveData['sort_order'] ?? $objectiveIndex,
                ]);

                if (! empty($objectiveData['department_ids'])) {
                    $objective->departments()->sync($objectiveData['department_ids']);
                }

                if (! empty($objectiveData['responsible_user_ids'])) {
                    $objective->responsibleUsers()->sync($objectiveData['responsible_user_ids']);
                }

                foreach ($objectiveData['kra'] ?? [] as $kraIndex => $kraData) {
                    KeyResultArea::query()->create([
                        'organization_id' => $organizationId,
                        'strategic_objective_id' => $objective->id,
                        'department_id' => $kraData['department_id'] ?? null,
                        'code' => $kraData['code'] ?? null,
                        'title' => $kraData['title'],
                        'description' => $kraData['description'] ?? null,
                        'priority' => $kraData['priority'] ?? 0,
                        'status' => $kraData['status'] ?? 'active',
                        'sort_order' => $kraData['sort_order'] ?? $kraIndex,
                    ]);
                }

                foreach ($objectiveData['outcomes'] ?? [] as $outcomeIndex => $outcomeData) {
                    $outcome = StrategicOutcome::query()->create([
                        'organization_id' => $organizationId,
                        'strategic_plan_id' => $plan->id,
                        'strategic_objective_id' => $objective->id,
                        'department_id' => $outcomeData['department_id'] ?? null,
                        'code' => $outcomeData['code'] ?? null,
                        'title' => $outcomeData['title'],
                        'description' => $outcomeData['description'] ?? null,
                        'expected_change' => $outcomeData['expected_change'] ?? null,
                        'target_period' => $outcomeData['target_period'] ?? null,
                        'status' => $outcomeData['status'] ?? 'active',
                        'sort_order' => $outcomeData['sort_order'] ?? $outcomeIndex,
                    ]);

                    foreach ($outcomeData['indicators'] ?? [] as $indicatorData) {
                        StrategicIndicator::query()->create([
                            'organization_id' => $organizationId,
                            'strategic_plan_id' => $plan->id,
                            'strategic_objective_id' => $objective->id,
                            'strategic_outcome_id' => $outcome->id,
                            'department_id' => $indicatorData['department_id'] ?? null,
                            'indicator_id' => $indicatorData['indicator_id'] ?? null,
                            'code' => $indicatorData['code'] ?? null,
                            'name' => $indicatorData['name'],
                            'definition' => $indicatorData['definition'] ?? null,
                            'measurement_unit' => $indicatorData['measurement_unit'] ?? null,
                            'measurement_direction' => $indicatorData['measurement_direction'] ?? null,
                            'baseline_value' => $indicatorData['baseline_value'] ?? null,
                            'baseline_date' => $indicatorData['baseline_date'] ?? null,
                            'target_value' => $indicatorData['target_value'] ?? null,
                            'target_period' => $indicatorData['target_period'] ?? null,
                            'data_source' => $indicatorData['data_source'] ?? null,
                            'measurement_frequency' => $indicatorData['measurement_frequency'] ?? null,
                            'status' => $indicatorData['status'] ?? 'active',
                        ]);
                    }
                }

                foreach ($objectiveData['indicators'] ?? [] as $indicatorData) {
                    StrategicIndicator::query()->create([
                        'organization_id' => $organizationId,
                        'strategic_plan_id' => $plan->id,
                        'strategic_objective_id' => $objective->id,
                        'department_id' => $indicatorData['department_id'] ?? null,
                        'indicator_id' => $indicatorData['indicator_id'] ?? null,
                        'code' => $indicatorData['code'] ?? null,
                        'name' => $indicatorData['name'],
                        'definition' => $indicatorData['definition'] ?? null,
                        'measurement_unit' => $indicatorData['measurement_unit'] ?? null,
                        'measurement_direction' => $indicatorData['measurement_direction'] ?? null,
                        'baseline_value' => $indicatorData['baseline_value'] ?? null,
                        'baseline_date' => $indicatorData['baseline_date'] ?? null,
                        'target_value' => $indicatorData['target_value'] ?? null,
                        'target_period' => $indicatorData['target_period'] ?? null,
                        'data_source' => $indicatorData['data_source'] ?? null,
                        'measurement_frequency' => $indicatorData['measurement_frequency'] ?? null,
                        'status' => $indicatorData['status'] ?? 'active',
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $priorities
     */
    protected function syncPriorities(StrategicPlan $plan, array $priorities): void
    {
        foreach ($priorities as $index => $priorityData) {
            StrategicPlanPriority::query()->create([
                'organization_id' => $plan->organization_id,
                'strategic_plan_id' => $plan->id,
                'department_id' => $priorityData['department_id'] ?? null,
                'code' => $priorityData['code'] ?? null,
                'title' => $priorityData['title'],
                'description' => $priorityData['description'] ?? null,
                'status' => $priorityData['status'] ?? 'active',
                'sort_order' => $priorityData['sort_order'] ?? $index,
            ]);
        }
    }

    protected function copyPriorities(StrategicPlan $source, StrategicPlan $target): void
    {
        $source->loadMissing('priorities');

        foreach ($source->priorities as $priority) {
            StrategicPlanPriority::query()->create([
                'organization_id' => $target->organization_id,
                'strategic_plan_id' => $target->id,
                'department_id' => $priority->department_id,
                'code' => $priority->code,
                'title' => $priority->title,
                'description' => $priority->description,
                'status' => $priority->status,
                'sort_order' => $priority->sort_order,
            ]);
        }
    }

    protected function clearPriorities(StrategicPlan $plan): void
    {
        $plan->priorities()->delete();
    }

    protected function copyNestedStructure(StrategicPlan $source, StrategicPlan $target): void
    {
        $source = $this->loadFullStructure($source);
        $themeMap = [];

        foreach ($source->themes as $theme) {
            $copy = StrategicTheme::query()->create([
                'organization_id' => $target->organization_id,
                'strategic_plan_id' => $target->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'sort_order' => $theme->sort_order,
            ]);
            $themeMap[$theme->id] = $copy->id;
        }

        foreach ($source->goals as $goal) {
            $goalCopy = StrategicGoal::query()->create([
                'organization_id' => $target->organization_id,
                'strategic_plan_id' => $target->id,
                'strategic_theme_id' => $goal->strategic_theme_id
                    ? ($themeMap[$goal->strategic_theme_id] ?? null)
                    : null,
                'code' => $goal->code,
                'title' => $goal->title,
                'category' => $goal->category,
                'priority' => $goal->priority,
                'owner_user_id' => $goal->owner_user_id,
                'description' => $goal->description,
                'expected_result' => $goal->expected_result,
                'start_date' => $goal->start_date,
                'end_date' => $goal->end_date,
                'status' => $goal->status,
                'sort_order' => $goal->sort_order,
            ]);

            $goalCopy->departments()->sync($goal->departments->pluck('id'));

            foreach ($goal->objectives as $objective) {
                $objectiveCopy = StrategicObjective::query()->create([
                    'organization_id' => $target->organization_id,
                    'strategic_goal_id' => $goalCopy->id,
                    'code' => $objective->code,
                    'title' => $objective->title,
                    'priority' => $objective->priority,
                    'description' => $objective->description,
                    'expected_result' => $objective->expected_result,
                    'start_date' => $objective->start_date,
                    'end_date' => $objective->end_date,
                    'status' => $objective->status,
                    'sort_order' => $objective->sort_order,
                ]);

                $objectiveCopy->departments()->sync($objective->departments->pluck('id'));
                $objectiveCopy->responsibleUsers()->sync($objective->responsibleUsers->pluck('id'));

                foreach ($objective->keyResultAreas as $kra) {
                    KeyResultArea::query()->create([
                        'organization_id' => $target->organization_id,
                        'strategic_objective_id' => $objectiveCopy->id,
                        'department_id' => $kra->department_id,
                        'code' => $kra->code,
                        'title' => $kra->title,
                        'description' => $kra->description,
                        'priority' => $kra->priority,
                        'status' => $kra->status,
                        'sort_order' => $kra->sort_order,
                    ]);
                }

                foreach ($objective->outcomes as $outcome) {
                    $outcomeCopy = StrategicOutcome::query()->create([
                        'organization_id' => $target->organization_id,
                        'strategic_plan_id' => $target->id,
                        'strategic_objective_id' => $objectiveCopy->id,
                        'department_id' => $outcome->department_id,
                        'code' => $outcome->code,
                        'title' => $outcome->title,
                        'description' => $outcome->description,
                        'expected_change' => $outcome->expected_change,
                        'target_period' => $outcome->target_period,
                        'status' => $outcome->status,
                        'sort_order' => $outcome->sort_order,
                    ]);

                    foreach ($outcome->indicators ?? [] as $indicator) {
                        StrategicIndicator::query()->create([
                            'organization_id' => $target->organization_id,
                            'strategic_plan_id' => $target->id,
                            'strategic_objective_id' => $objectiveCopy->id,
                            'strategic_outcome_id' => $outcomeCopy->id,
                            'department_id' => $indicator->department_id,
                            'indicator_id' => $indicator->indicator_id,
                            'code' => $indicator->code,
                            'name' => $indicator->name,
                            'definition' => $indicator->definition,
                            'measurement_unit' => $indicator->measurement_unit,
                            'measurement_direction' => $indicator->measurement_direction,
                            'baseline_value' => $indicator->baseline_value,
                            'baseline_date' => $indicator->baseline_date,
                            'target_value' => $indicator->target_value,
                            'target_period' => $indicator->target_period,
                            'data_source' => $indicator->data_source,
                            'measurement_frequency' => $indicator->measurement_frequency,
                            'status' => $indicator->status,
                        ]);
                    }
                }

                foreach ($objective->indicators as $indicator) {
                    if ($indicator->strategic_outcome_id) {
                        continue;
                    }

                    StrategicIndicator::query()->create([
                        'organization_id' => $target->organization_id,
                        'strategic_plan_id' => $target->id,
                        'strategic_objective_id' => $objectiveCopy->id,
                        'department_id' => $indicator->department_id,
                        'indicator_id' => $indicator->indicator_id,
                        'code' => $indicator->code,
                        'name' => $indicator->name,
                        'definition' => $indicator->definition,
                        'measurement_unit' => $indicator->measurement_unit,
                        'measurement_direction' => $indicator->measurement_direction,
                        'baseline_value' => $indicator->baseline_value,
                        'baseline_date' => $indicator->baseline_date,
                        'target_value' => $indicator->target_value,
                        'target_period' => $indicator->target_period,
                        'data_source' => $indicator->data_source,
                        'measurement_frequency' => $indicator->measurement_frequency,
                        'status' => $indicator->status,
                    ]);
                }
            }
        }
    }

    protected function clearNestedStructure(StrategicPlan $plan): void
    {
        $plan->load([
            'goals.objectives.keyResultAreas',
            'goals.objectives.outcomes.indicators',
            'goals.objectives.indicators',
            'goals.objectives',
            'goals',
            'themes',
        ]);

        foreach ($plan->goals as $goal) {
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
        }

        $plan->themes()->delete();
    }

    protected function assertOrg(StrategicPlan $plan): void
    {
        if ($plan->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
