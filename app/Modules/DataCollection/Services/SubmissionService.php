<?php

namespace App\Modules\DataCollection\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\MonitoringPlanStatus;
use App\Enums\SubmissionStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\DataCollection\Models\ImportJob;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\DataCollection\Models\SubmissionValue;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
        protected DataQualityCheckService $qualityCheckService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Submission
    {
        return DB::transaction(function () use ($data) {
            $item = MonitoringPlanItem::query()
                ->where('organization_id', $this->organizationContext->id())
                ->where('id', $data['monitoring_plan_item_id'])
                ->firstOrFail();

            $plan = MonitoringPlan::query()->findOrFail($item->monitoring_plan_id);
            $this->assertPlanActive($plan);

            $submission = Submission::query()->create([
                'organization_id' => $plan->organization_id,
                'reporting_assignment_id' => $data['reporting_assignment_id'] ?? null,
                'monitoring_plan_id' => $plan->id,
                'monitoring_plan_item_id' => $item->id,
                'indicator_id' => $item->indicator_id,
                'reporting_period_id' => $data['reporting_period_id'] ?? null,
                'submitted_by' => Auth::id(),
                'status' => SubmissionStatus::Draft->value,
                'numerator' => $data['numerator'] ?? null,
                'denominator' => $data['denominator'] ?? null,
                'value' => $data['value'] ?? $this->calculateValue($data),
                'disaggregations' => $data['disaggregations'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncValues($submission, $data['values'] ?? []);
            $this->auditService->log(AuditAction::Created, 'submissions', Submission::class, $submission->id);

            return $this->load($submission);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Submission $submission, array $data): Submission
    {
        $this->assertOrg($submission);
        $this->assertEditable($submission);

        return DB::transaction(function () use ($submission, $data) {
            $submission->fill([
                'numerator' => $data['numerator'] ?? $submission->numerator,
                'denominator' => $data['denominator'] ?? $submission->denominator,
                'value' => array_key_exists('value', $data) ? $data['value'] : ($this->calculateValue($data) ?? $submission->value),
                'disaggregations' => $data['disaggregations'] ?? $submission->disaggregations,
                'notes' => $data['notes'] ?? $submission->notes,
                'reporting_period_id' => $data['reporting_period_id'] ?? $submission->reporting_period_id,
            ])->save();

            if (array_key_exists('values', $data)) {
                $this->syncValues($submission, $data['values'] ?? []);
            }

            $this->auditService->log(AuditAction::Updated, 'submissions', Submission::class, $submission->id);

            return $this->load($submission->fresh());
        });
    }

    public function submit(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        if ($submission->value === null && $submission->numerator === null) {
            throw ValidationException::withMessages([
                'value' => ['A value or numerator is required before submission.'],
            ]);
        }

        $flags = $this->qualityCheckService->evaluate($submission);
        if ($flags !== []) {
            $submission->notes = trim(($submission->notes ?? '')."\nQuality flags: ".implode('; ', $flags));
            $submission->save();
        }

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Submitted->value,
            ApprovalAction::Submitted,
            [SubmissionStatus::Draft->value, SubmissionStatus::Returned->value],
            $comment,
            'submissions',
        ));

        $result->submitted_at = now();
        $result->submitted_by = Auth::id();
        $result->save();

        if ($result->reporting_assignment_id) {
            ReportingAssignment::query()
                ->where('id', $result->reporting_assignment_id)
                ->update(['status' => 'submitted']);
        }

        return $this->load($result->fresh());
    }

    public function returnForCorrection(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        return $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Returned->value,
            ApprovalAction::Returned,
            [SubmissionStatus::Submitted->value, SubmissionStatus::Verified->value],
            $comment,
            'submissions',
            true,
        ));
    }

    public function verify(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Verified->value,
            ApprovalAction::Verified,
            [SubmissionStatus::Submitted->value],
            $comment,
            'submissions',
        ));

        $result->verified_at = now();
        $result->verified_by = Auth::id();
        $result->save();

        return $this->load($result->fresh());
    }

    public function validate(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Validated->value,
            ApprovalAction::Validated,
            [SubmissionStatus::Verified->value],
            $comment,
            'submissions',
        ));

        $result->validated_at = now();
        $result->validated_by = Auth::id();
        $result->save();

        return $this->load($result->fresh());
    }

    public function approve(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Approved->value,
            ApprovalAction::Approved,
            [SubmissionStatus::Validated->value],
            $comment,
            'submissions',
            false,
            true,
        ));

        return $this->load($result);
    }

    public function publish(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Published->value,
            ApprovalAction::Published,
            [SubmissionStatus::Approved->value],
            $comment,
            'submissions',
        ));

        $result->published_at = now();
        $result->save();

        return $this->load($result->fresh());
    }

    public function lock(Submission $submission, ?string $comment = null): Submission
    {
        $this->assertOrg($submission);

        $result = $this->load($this->workflowService->transition(
            $submission,
            SubmissionStatus::Locked->value,
            ApprovalAction::Locked,
            [SubmissionStatus::Published->value],
            $comment,
            'submissions',
        ));

        $result->locked_at = now();
        $result->save();

        return $this->load($result->fresh());
    }

    /**
     * @return array{job: ImportJob, created: int}
     */
    public function import(UploadedFile $file, ?int $monitoringPlanId = null): array
    {
        $path = $file->store('imports/submissions', 'local');

        $job = ImportJob::query()->create([
            'organization_id' => $this->organizationContext->id(),
            'monitoring_plan_id' => $monitoringPlanId,
            'uploaded_by' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'status' => 'processing',
        ]);

        $content = Storage::disk('local')->get($path) ?? '';
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $errors = [];
        $success = 0;
        $total = 0;

        foreach ($lines as $index => $line) {
            if ($index === 0 && str_contains(strtolower($line), 'indicator')) {
                continue;
            }

            if (blank($line)) {
                continue;
            }

            $total++;
            $cols = str_getcsv($line);

            if (count($cols) < 3) {
                $errors[] = ['row' => $index + 1, 'message' => 'Expected item_id,period_id,value'];

                continue;
            }

            try {
                $this->create([
                    'monitoring_plan_item_id' => (int) $cols[0],
                    'reporting_period_id' => filled($cols[1] ?? null) ? (int) $cols[1] : null,
                    'value' => $cols[2],
                    'notes' => $cols[3] ?? 'Imported via CSV',
                ]);
                $success++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $index + 1, 'message' => $e->getMessage()];
            }
        }

        $job->update([
            'status' => $errors === [] ? 'completed' : 'completed_with_errors',
            'total_rows' => $total,
            'success_rows' => $success,
            'failed_rows' => count($errors),
            'errors' => $errors,
        ]);

        return ['job' => $job->fresh(), 'created' => $success];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function calculateValue(array $data): ?string
    {
        if (isset($data['value'])) {
            return (string) $data['value'];
        }

        $numerator = $data['numerator'] ?? null;
        $denominator = $data['denominator'] ?? null;

        if ($numerator !== null && $denominator !== null && (float) $denominator != 0.0) {
            return (string) round(((float) $numerator / (float) $denominator) * 100, 4);
        }

        return $numerator !== null ? (string) $numerator : null;
    }

    /**
     * @param  list<array<string, mixed>>  $values
     */
    protected function syncValues(Submission $submission, array $values): void
    {
        $submission->values()->delete();

        foreach ($values as $value) {
            SubmissionValue::query()->create([
                'organization_id' => $submission->organization_id,
                'submission_id' => $submission->id,
                'dimension' => $value['dimension'] ?? null,
                'label' => $value['label'] ?? null,
                'value' => $value['value'] ?? null,
            ]);
        }
    }

    protected function assertPlanActive(MonitoringPlan $plan): void
    {
        $status = $plan->status instanceof MonitoringPlanStatus ? $plan->status->value : (string) $plan->status;

        if ($status !== MonitoringPlanStatus::Active->value) {
            throw ValidationException::withMessages([
                'monitoring_plan_id' => ['Submissions require an active monitoring plan.'],
            ]);
        }
    }

    protected function assertEditable(Submission $submission): void
    {
        $status = $submission->status instanceof SubmissionStatus ? $submission->status->value : (string) $submission->status;

        if (! in_array($status, [SubmissionStatus::Draft->value, SubmissionStatus::Returned->value], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only draft or returned submissions can be edited.'],
            ]);
        }
    }

    protected function load(Submission $submission): Submission
    {
        return $submission->load(['values', 'indicator', 'assignment', 'approvalHistories']);
    }

    protected function assertOrg(Submission $submission): void
    {
        if ($submission->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
