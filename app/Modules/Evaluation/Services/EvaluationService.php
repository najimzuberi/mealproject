<?php

namespace App\Modules\Evaluation\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\EvaluationStatus;
use App\Enums\LearningActionStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Models\EvaluationFinding;
use App\Modules\Evaluation\Models\EvaluationQuestion;
use App\Modules\Evaluation\Models\EvaluationRecommendation;
use App\Modules\Learning\Models\LearningAction;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Evaluation
    {
        return DB::transaction(function () use ($data) {
            $evaluation = Evaluation::query()->create([
                ...$this->attributes($data),
                'organization_id' => $this->organizationContext->id(),
                'status' => EvaluationStatus::Draft->value,
                'created_by' => Auth::id(),
            ]);

            $this->syncQuestions($evaluation, $data['questions'] ?? []);
            $this->auditService->log(AuditAction::Created, 'evaluations', Evaluation::class, $evaluation->id);

            return $this->load($evaluation);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Evaluation $evaluation, array $data): Evaluation
    {
        $this->assertOrg($evaluation);
        $status = $evaluation->status instanceof EvaluationStatus ? $evaluation->status->value : (string) $evaluation->status;

        if (in_array($status, [EvaluationStatus::Completed->value, EvaluationStatus::Archived->value], true)) {
            throw ValidationException::withMessages(['status' => ['Completed evaluations cannot be edited.']]);
        }

        return DB::transaction(function () use ($evaluation, $data) {
            $evaluation->fill($this->attributes($data))->save();
            if (array_key_exists('questions', $data)) {
                $this->syncQuestions($evaluation, $data['questions'] ?? []);
            }
            $this->auditService->log(AuditAction::Updated, 'evaluations', Evaluation::class, $evaluation->id);

            return $this->load($evaluation->fresh());
        });
    }

    public function submit(Evaluation $evaluation, ?string $comment = null): Evaluation
    {
        $this->assertOrg($evaluation);

        return $this->load($this->workflowService->transition(
            $evaluation,
            EvaluationStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [EvaluationStatus::Draft->value, EvaluationStatus::Returned->value],
            $comment,
            'evaluations',
        ));
    }

    public function returnForCorrection(Evaluation $evaluation, ?string $comment = null): Evaluation
    {
        $this->assertOrg($evaluation);

        return $this->load($this->workflowService->transition(
            $evaluation,
            EvaluationStatus::Returned->value,
            ApprovalAction::Returned,
            [EvaluationStatus::UnderReview->value],
            $comment,
            'evaluations',
            true,
        ));
    }

    public function approve(Evaluation $evaluation, ?string $comment = null): Evaluation
    {
        $this->assertOrg($evaluation);

        $result = $this->workflowService->transition(
            $evaluation,
            EvaluationStatus::Approved->value,
            ApprovalAction::Approved,
            [EvaluationStatus::UnderReview->value],
            $comment,
            'evaluations',
            false,
            true,
        );

        $result->status = EvaluationStatus::InProgress->value;
        $result->save();

        return $this->load($result->fresh());
    }

    public function complete(Evaluation $evaluation, ?string $comment = null): Evaluation
    {
        $this->assertOrg($evaluation);

        $result = $this->workflowService->transition(
            $evaluation,
            EvaluationStatus::Completed->value,
            ApprovalAction::Archived,
            [EvaluationStatus::InProgress->value, EvaluationStatus::Approved->value],
            $comment,
            'evaluations',
        );

        $result->completed_at = now();
        $result->save();

        return $this->load($result->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addFinding(Evaluation $evaluation, array $data): EvaluationFinding
    {
        $this->assertOrg($evaluation);

        $finding = EvaluationFinding::query()->create([
            'organization_id' => $evaluation->organization_id,
            'evaluation_id' => $evaluation->id,
            'reference' => $data['reference'] ?? 'F-'.($evaluation->findings()->count() + 1),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'supporting_evidence' => $data['supporting_evidence'] ?? null,
            'project_id' => $data['project_id'] ?? $evaluation->project_id,
            'result_id' => $data['result_id'] ?? null,
            'indicator_id' => $data['indicator_id'] ?? null,
            'conclusion' => $data['conclusion'] ?? null,
            'significance' => $data['significance'] ?? null,
        ]);

        $this->auditService->log(AuditAction::Created, 'evaluations', EvaluationFinding::class, $finding->id);

        return $finding;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addRecommendation(Evaluation $evaluation, array $data): EvaluationRecommendation
    {
        $this->assertOrg($evaluation);

        $recommendation = EvaluationRecommendation::query()->create([
            'organization_id' => $evaluation->organization_id,
            'evaluation_id' => $evaluation->id,
            'evaluation_finding_id' => $data['evaluation_finding_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'supporting_evidence' => $data['supporting_evidence'] ?? null,
            'responsible_department_id' => $data['responsible_department_id'] ?? null,
        ]);

        $this->auditService->log(AuditAction::Created, 'evaluations', EvaluationRecommendation::class, $recommendation->id);

        return $recommendation->load('finding');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function respondToRecommendation(EvaluationRecommendation $recommendation, array $data): EvaluationRecommendation
    {
        if ($recommendation->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        $status = $data['management_response_status'];
        $recommendation->fill([
            'management_response_status' => $status,
            'management_response' => $data['management_response'] ?? null,
            'response_date' => $data['response_date'] ?? now()->toDateString(),
            'responsible_manager_id' => $data['responsible_manager_id'] ?? Auth::id(),
        ])->save();

        if (in_array($status, ['accepted', 'partially_accepted'], true)) {
            LearningAction::query()->create([
                'organization_id' => $recommendation->organization_id,
                'actionable_type' => EvaluationRecommendation::class,
                'actionable_id' => $recommendation->id,
                'description' => $data['action_description'] ?? ('Follow up: '.$recommendation->title),
                'responsible_person_id' => $data['responsible_person_id'] ?? $recommendation->responsible_manager_id,
                'department_id' => $recommendation->responsible_department_id,
                'due_on' => $data['due_on'] ?? null,
                'required_evidence' => $data['required_evidence'] ?? null,
                'status' => LearningActionStatus::Open->value,
                'created_by' => Auth::id(),
            ]);
        }

        $this->auditService->log(AuditAction::Updated, 'evaluations', EvaluationRecommendation::class, $recommendation->id, null, [
            'management_response_status' => $status,
        ]);

        return $recommendation->fresh()->load(['finding', 'actions']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return collect($data)->only([
            'code', 'title', 'evaluation_type', 'programme_id', 'project_id', 'purpose', 'scope',
            'starts_on', 'ends_on', 'evaluation_lead_id', 'evaluation_team', 'methodology',
            'data_collection_methods', 'target_population', 'sample_size', 'geographic_coverage',
            'tools', 'timeline_notes',
        ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    protected function syncQuestions(Evaluation $evaluation, array $questions): void
    {
        $evaluation->questions()->delete();
        foreach ($questions as $index => $question) {
            EvaluationQuestion::query()->create([
                'organization_id' => $evaluation->organization_id,
                'evaluation_id' => $evaluation->id,
                'question' => $question['question'],
                'criterion' => $question['criterion'] ?? null,
                'sort_order' => $question['sort_order'] ?? $index,
            ]);
        }
    }

    protected function load(Evaluation $evaluation): Evaluation
    {
        return $evaluation->load(['questions', 'findings', 'recommendations.actions', 'approvalHistories', 'project', 'programme']);
    }

    protected function assertOrg(Evaluation $evaluation): void
    {
        if ($evaluation->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
