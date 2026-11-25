<?php

namespace App\Modules\Dqa\Services;

use App\Enums\AuditAction;
use App\Enums\DqaAssessmentStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\DataCollection\Services\DataQualityCheckService;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Dqa\Models\DqaIssue;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DqaService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected DataQualityCheckService $qualityCheckService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssessment(array $data): DqaAssessment
    {
        return DB::transaction(function () use ($data) {
            $submission = Submission::query()
                ->where('organization_id', $this->organizationContext->id())
                ->where('id', $data['submission_id'])
                ->firstOrFail();

            $scores = [
                'validity_score' => $data['validity_score'] ?? null,
                'reliability_score' => $data['reliability_score'] ?? null,
                'completeness_score' => $data['completeness_score'] ?? null,
                'timeliness_score' => $data['timeliness_score'] ?? null,
                'precision_score' => $data['precision_score'] ?? null,
                'integrity_score' => $data['integrity_score'] ?? null,
            ];

            $assessment = DqaAssessment::query()->create([
                'organization_id' => $submission->organization_id,
                'submission_id' => $submission->id,
                'assessed_by' => Auth::id(),
                'status' => DqaAssessmentStatus::InProgress->value,
                ...$scores,
                'overall_score' => $this->overallScore($scores),
                'summary' => $data['summary'] ?? null,
            ]);

            foreach ($this->qualityCheckService->evaluate($submission) as $flag) {
                DqaIssue::query()->create([
                    'organization_id' => $assessment->organization_id,
                    'dqa_assessment_id' => $assessment->id,
                    'submission_id' => $submission->id,
                    'dimension' => $flag,
                    'severity' => in_array($flag, ['formula_inconsistency', 'potential_outlier'], true) ? 'high' : 'medium',
                    'title' => str_replace('_', ' ', ucfirst($flag)),
                    'description' => 'Automated quality flag for human review.',
                    'status' => 'open',
                    'assigned_to' => $data['assigned_to'] ?? Auth::id(),
                ]);
            }

            $this->auditService->log(AuditAction::Created, 'dqa', DqaAssessment::class, $assessment->id);

            return $this->load($assessment);
        });
    }

    public function complete(DqaAssessment $assessment, ?string $summary = null): DqaAssessment
    {
        $this->assertOrg($assessment);

        $assessment->status = DqaAssessmentStatus::Completed->value;
        $assessment->summary = $summary ?? $assessment->summary;
        $assessment->completed_at = now();
        $assessment->save();

        $this->auditService->log(AuditAction::Updated, 'dqa', DqaAssessment::class, $assessment->id, null, [
            'status' => DqaAssessmentStatus::Completed->value,
        ]);

        return $this->load($assessment->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createIssue(array $data): DqaIssue
    {
        $assessment = DqaAssessment::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where('id', $data['dqa_assessment_id'])
            ->firstOrFail();

        $issue = DqaIssue::query()->create([
            'organization_id' => $assessment->organization_id,
            'dqa_assessment_id' => $assessment->id,
            'submission_id' => $data['submission_id'] ?? $assessment->submission_id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'dimension' => $data['dimension'] ?? null,
            'severity' => $data['severity'] ?? 'medium',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'open',
        ]);

        $this->auditService->log(AuditAction::Created, 'dqa', DqaIssue::class, $issue->id);

        return $issue->load(['assessment', 'assignee']);
    }

    public function resolveIssue(DqaIssue $issue, string $resolutionNotes): DqaIssue
    {
        $this->assertOrgIssue($issue);

        if (blank($resolutionNotes)) {
            throw ValidationException::withMessages([
                'resolution_notes' => ['Resolution notes are required.'],
            ]);
        }

        $issue->status = 'resolved';
        $issue->resolution_notes = $resolutionNotes;
        $issue->resolved_at = now();
        $issue->save();

        $this->auditService->log(AuditAction::Updated, 'dqa', DqaIssue::class, $issue->id, null, [
            'status' => 'resolved',
        ]);

        return $issue->fresh()->load(['assessment', 'assignee']);
    }

    /**
     * @param  array<string, int|null>  $scores
     */
    protected function overallScore(array $scores): ?float
    {
        $values = array_values(array_filter($scores, fn ($v) => $v !== null));

        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }

    protected function load(DqaAssessment $assessment): DqaAssessment
    {
        return $assessment->load(['submission.indicator', 'issues', 'assessor']);
    }

    protected function assertOrg(DqaAssessment $assessment): void
    {
        if ($assessment->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }

    protected function assertOrgIssue(DqaIssue $issue): void
    {
        if ($issue->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
