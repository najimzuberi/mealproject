<?php

namespace App\Modules\ResultsFramework\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\ResultLevel;
use App\Enums\WorkflowStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultAssumption;
use App\Modules\ResultsFramework\Models\ResultRisk;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultsFrameworkService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ResultsFramework
    {
        return DB::transaction(function () use ($data) {
            $project = Project::query()
                ->where('organization_id', $this->organizationContext->id())
                ->where('id', $data['project_id'])
                ->firstOrFail();

            $framework = ResultsFramework::query()->create([
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'title' => $data['title'],
                'theory_of_change' => $data['theory_of_change'] ?? null,
                'status' => WorkflowStatus::Draft->value,
                'version' => 1,
            ]);

            foreach ($data['results'] ?? [] as $resultData) {
                $this->createResultNode($framework, $resultData, null);
            }

            $this->auditService->log(AuditAction::Created, 'results_frameworks', ResultsFramework::class, $framework->id);

            return $this->load($framework);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ResultsFramework $framework, array $data): ResultsFramework
    {
        $this->assertOrg($framework);
        $this->workflowService->assertEditable($framework, [
            WorkflowStatus::Draft->value,
            WorkflowStatus::Returned->value,
            WorkflowStatus::UnderReview->value,
        ]);

        return DB::transaction(function () use ($framework, $data) {
            $framework->fill(collect($data)->only(['title', 'theory_of_change'])->all())->save();

            if (array_key_exists('results', $data)) {
                $framework->results()->each(function (Result $result) {
                    $result->assumptions()->delete();
                    $result->risks()->delete();
                    $result->delete();
                });
                foreach ($data['results'] ?? [] as $resultData) {
                    $this->createResultNode($framework, $resultData, null);
                }
            }

            $this->auditService->log(AuditAction::Updated, 'results_frameworks', ResultsFramework::class, $framework->id);

            return $this->load($framework->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addResult(ResultsFramework $framework, array $data): Result
    {
        $this->assertOrg($framework);
        $this->workflowService->assertEditable($framework, [WorkflowStatus::Draft->value, WorkflowStatus::UnderReview->value]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = Result::query()
                ->where('results_framework_id', $framework->id)
                ->where('id', $parentId)
                ->first();
            if (! $parent) {
                throw ValidationException::withMessages(['parent_id' => ['Parent result not found in this framework.']]);
            }
        }

        return $this->createResultNode($framework, $data, $parentId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateResult(Result $result, array $data): Result
    {
        $result->loadMissing('framework');
        $this->assertOrg($result->framework);
        $this->workflowService->assertEditable($result->framework, [
            WorkflowStatus::Draft->value,
            WorkflowStatus::UnderReview->value,
        ]);

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $parent = Result::query()
                ->where('results_framework_id', $result->results_framework_id)
                ->where('id', $data['parent_id'])
                ->first();
            if (! $parent || $parent->id === $result->id) {
                throw ValidationException::withMessages(['parent_id' => ['Parent result not found in this framework.']]);
            }
        }

        $result->fill(collect($data)->only([
            'parent_id', 'level', 'code', 'title', 'description', 'sort_order', 'department_id',
        ])->filter(fn ($v) => $v !== null)->all())->save();

        $this->auditService->log(AuditAction::Updated, 'results_frameworks', Result::class, $result->id);

        return $result->fresh()->load(['assumptions', 'risks', 'department', 'children']);
    }

    public function linkIndicator(Result $result, int $indicatorId): Indicator
    {
        $this->assertOrg($result->framework);

        $indicator = Indicator::query()
            ->where('organization_id', $result->organization_id)
            ->where('id', $indicatorId)
            ->firstOrFail();

        $indicator->result_id = $result->id;
        $indicator->results_framework_id = $result->results_framework_id;
        $indicator->result_level = $result->level instanceof ResultLevel ? $result->level->value : $result->level;
        $indicator->project_id = $result->framework->project_id;
        $indicator->save();

        return $indicator->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function hierarchy(ResultsFramework $framework): array
    {
        $this->assertOrg($framework);
        $framework->load(['results.assumptions', 'results.risks', 'results.department']);

        $roots = $framework->results->whereNull('parent_id')->sortBy('sort_order')->values();

        return [
            'id' => $framework->id,
            'ulid' => $framework->ulid,
            'title' => $framework->title,
            'theory_of_change' => $framework->theory_of_change,
            'status' => $framework->status instanceof \BackedEnum ? $framework->status->value : $framework->status,
            'version' => $framework->version,
            'project_id' => $framework->project_id,
            'tree' => $roots->map(fn (Result $result) => $this->mapResultTree($result, $framework->results))->all(),
        ];
    }

    public function submit(ResultsFramework $framework, ?string $comment = null): ResultsFramework
    {
        $this->assertOrg($framework);

        $transitioned = $this->workflowService->transition(
            $framework,
            WorkflowStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [WorkflowStatus::Draft->value, WorkflowStatus::Returned->value],
            $comment,
            'results_frameworks',
        );

        $transitioned->submitted_at = now();
        $transitioned->save();

        return $this->load($transitioned);
    }

    public function returnForCorrection(ResultsFramework $framework, string $comment): ResultsFramework
    {
        $this->assertOrg($framework);

        return $this->load($this->workflowService->transition(
            $framework,
            WorkflowStatus::Returned->value,
            ApprovalAction::Returned,
            [WorkflowStatus::UnderReview->value],
            $comment,
            'results_frameworks',
            requireComment: true,
        ));
    }

    public function approve(ResultsFramework $framework, ?string $comment = null): ResultsFramework
    {
        $this->assertOrg($framework);

        return $this->load($this->workflowService->transition(
            $framework,
            WorkflowStatus::Approved->value,
            ApprovalAction::Approved,
            [WorkflowStatus::UnderReview->value],
            $comment,
            'results_frameworks',
            setApprovedMeta: true,
        ));
    }

    public function activate(ResultsFramework $framework, ?string $comment = null): ResultsFramework
    {
        $this->assertOrg($framework);

        return $this->load($this->workflowService->transition(
            $framework,
            WorkflowStatus::Active->value,
            ApprovalAction::Activated,
            [WorkflowStatus::Approved->value],
            $comment,
            'results_frameworks',
        ));
    }

    public function archive(ResultsFramework $framework, ?string $comment = null): ResultsFramework
    {
        $this->assertOrg($framework);

        return $this->load($this->workflowService->transition(
            $framework,
            WorkflowStatus::Archived->value,
            ApprovalAction::Archived,
            [WorkflowStatus::Active->value, WorkflowStatus::Approved->value, WorkflowStatus::Superseded->value],
            $comment,
            'results_frameworks',
        ));
    }

    public function createNewVersion(ResultsFramework $framework): ResultsFramework
    {
        $this->assertOrg($framework);
        $loaded = $this->load($framework);
        $loaded->load(['results.assumptions', 'results.risks', 'results.children']);

        return DB::transaction(function () use ($loaded) {
            $newVersion = ($loaded->version ?? 1) + 1;

            $draft = ResultsFramework::query()->create([
                'organization_id' => $loaded->organization_id,
                'project_id' => $loaded->project_id,
                'title' => $loaded->title.' (v'.$newVersion.')',
                'theory_of_change' => $loaded->theory_of_change,
                'status' => WorkflowStatus::Draft->value,
                'version' => $newVersion,
                'parent_framework_id' => $loaded->id,
            ]);

            $all = $loaded->results;
            $roots = $all->whereNull('parent_id')->sortBy('sort_order');
            foreach ($roots as $root) {
                $this->copyResultTree($draft, $root, $all, null);
            }

            $this->auditService->log(AuditAction::Created, 'results_frameworks', ResultsFramework::class, $draft->id);

            return $this->load($draft);
        });
    }

    public function deleteResult(Result $result): void
    {
        $result->loadMissing('framework');
        $this->assertOrg($result->framework);
        $this->workflowService->assertEditable($result->framework, [
            WorkflowStatus::Draft->value,
            WorkflowStatus::Returned->value,
            WorkflowStatus::UnderReview->value,
        ]);

        DB::transaction(function () use ($result): void {
            $result->load('children');
            $this->deleteResultTree($result);
        });

        $this->auditService->log(AuditAction::Deleted, 'results_frameworks', Result::class, $result->id);
    }

    protected function deleteResultTree(Result $result): void
    {
        $result->loadMissing('children');

        foreach ($result->children as $child) {
            $this->deleteResultTree($child);
        }

        $result->assumptions()->delete();
        $result->risks()->delete();
        $result->delete();
    }

    /**
     * @param  Collection<int, Result>  $all
     */
    protected function copyResultTree(ResultsFramework $target, Result $source, $all, ?int $parentId): void
    {
        $copy = Result::query()->create([
            'organization_id' => $target->organization_id,
            'results_framework_id' => $target->id,
            'parent_id' => $parentId,
            'level' => $source->level instanceof ResultLevel ? $source->level->value : $source->level,
            'code' => $source->code,
            'title' => $source->title,
            'description' => $source->description,
            'sort_order' => $source->sort_order,
            'department_id' => $source->department_id,
        ]);

        foreach ($source->assumptions as $assumption) {
            ResultAssumption::query()->create([
                'organization_id' => $target->organization_id,
                'result_id' => $copy->id,
                'description' => $assumption->description,
            ]);
        }

        foreach ($source->risks as $risk) {
            ResultRisk::query()->create([
                'organization_id' => $target->organization_id,
                'result_id' => $copy->id,
                'description' => $risk->description,
                'likelihood' => $risk->likelihood,
                'impact' => $risk->impact,
                'mitigation' => $risk->mitigation,
                'status' => $risk->status,
            ]);
        }

        foreach ($all->where('parent_id', $source->id)->sortBy('sort_order') as $child) {
            $this->copyResultTree($target, $child, $all, $copy->id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createResultNode(ResultsFramework $framework, array $data, ?int $parentId): Result
    {
        $level = $data['level'];
        if (! in_array($level, array_column(ResultLevel::cases(), 'value'), true)) {
            throw ValidationException::withMessages(['level' => ['Invalid result level.']]);
        }

        $result = Result::query()->create([
            'organization_id' => $framework->organization_id,
            'results_framework_id' => $framework->id,
            'parent_id' => $parentId ?? ($data['parent_id'] ?? null),
            'level' => $level,
            'code' => $data['code'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'department_id' => $data['department_id'] ?? null,
        ]);

        foreach ($data['assumptions'] ?? [] as $assumption) {
            ResultAssumption::query()->create([
                'organization_id' => $framework->organization_id,
                'result_id' => $result->id,
                'description' => is_array($assumption) ? $assumption['description'] : $assumption,
            ]);
        }

        foreach ($data['risks'] ?? [] as $risk) {
            ResultRisk::query()->create([
                'organization_id' => $framework->organization_id,
                'result_id' => $result->id,
                'description' => $risk['description'],
                'likelihood' => $risk['likelihood'] ?? null,
                'impact' => $risk['impact'] ?? null,
                'mitigation' => $risk['mitigation'] ?? null,
                'status' => $risk['status'] ?? 'open',
            ]);
        }

        foreach ($data['children'] ?? [] as $child) {
            $this->createResultNode($framework, $child, $result->id);
        }

        return $result->load(['assumptions', 'risks']);
    }

    /**
     * @param  Collection<int, Result>  $all
     * @return array<string, mixed>
     */
    protected function mapResultTree(Result $result, $all): array
    {
        $children = $all->where('parent_id', $result->id)->sortBy('sort_order')->values();

        return [
            'id' => $result->id,
            'ulid' => $result->ulid,
            'parent_id' => $result->parent_id,
            'level' => $result->level instanceof ResultLevel ? $result->level->value : $result->level,
            'code' => $result->code,
            'title' => $result->title,
            'description' => $result->description,
            'sort_order' => $result->sort_order,
            'department_id' => $result->department_id,
            'assumptions' => $result->assumptions->map(fn ($a) => ['id' => $a->id, 'description' => $a->description])->all(),
            'risks' => $result->risks->map(fn ($r) => [
                'id' => $r->id,
                'description' => $r->description,
                'likelihood' => $r->likelihood,
                'impact' => $r->impact,
                'mitigation' => $r->mitigation,
                'status' => $r->status,
            ])->all(),
            'children' => $children->map(fn (Result $child) => $this->mapResultTree($child, $all))->all(),
        ];
    }

    protected function load(ResultsFramework $framework): ResultsFramework
    {
        return $framework->load(['project', 'results.assumptions', 'results.risks', 'approvalHistories']);
    }

    protected function assertOrg(ResultsFramework $framework): void
    {
        if ($framework->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
