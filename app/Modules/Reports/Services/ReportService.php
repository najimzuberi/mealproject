<?php

namespace App\Modules\Reports\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Performance\Services\PerformanceAggregationService;
use App\Modules\Reports\Models\Report;
use App\Modules\Reports\Models\ReportIndicatorResult;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
        protected PerformanceAggregationService $performanceAggregationService,
        protected ReportExportService $reportExportService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data): Report
    {
        return DB::transaction(function () use ($data) {
            $type = $data['report_type'];
            $reference = $data['reference_number'] ?? $this->nextReference($type);

            $request = Request::create('/', 'GET', array_filter([
                'programme_id' => $data['programme_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'reporting_period_id' => $data['reporting_period_id'] ?? null,
                'indicator_id' => $data['indicator_id'] ?? null,
                'granularity' => match ($type) {
                    ReportType::Monthly->value => 'monthly',
                    ReportType::Annual->value => 'annual',
                    default => 'quarterly',
                },
            ]));

            $dashboard = $this->performanceAggregationService->dashboard($request);
            $results = $dashboard['indicator_performance'] ?? [];

            $report = Report::query()->create([
                'organization_id' => $this->organizationContext->id(),
                'reference_number' => $reference,
                'title' => $data['title'],
                'report_type' => $type,
                'status' => ReportStatus::Draft->value,
                'programme_id' => $data['programme_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'reporting_period_id' => $data['reporting_period_id'] ?? null,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'prepared_by' => Auth::id(),
                'executive_summary' => $data['executive_summary'] ?? $this->draftSummary($dashboard, true),
                'performance_summary' => $data['performance_summary'] ?? null,
                'challenges' => $data['challenges'] ?? null,
                'risks' => $data['risks'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'ai_assisted' => (bool) ($data['ai_assisted'] ?? true),
                'snapshot' => [
                    'summary' => $dashboard['summary'] ?? [],
                    'reporting_status' => $dashboard['reporting_status'] ?? [],
                    'target_vs_achievement' => $dashboard['target_vs_achievement'] ?? [],
                ],
            ]);

            foreach ($results as $row) {
                ReportIndicatorResult::query()->create([
                    'organization_id' => $report->organization_id,
                    'report_id' => $report->id,
                    'indicator_id' => $row['indicator_id'] ?? null,
                    'project_id' => $row['project_id'] ?? null,
                    'reporting_period_id' => $row['reporting_period_id'] ?? null,
                    'indicator_code' => $row['indicator_code'] ?? null,
                    'indicator_name' => $row['indicator_name'] ?? null,
                    'baseline' => $row['baseline'] ?? null,
                    'target' => $row['target'] ?? null,
                    'actual' => $row['actual'] ?? null,
                    'achievement_percent' => $row['achievement_percent'] ?? null,
                    'variance' => $row['variance'] ?? null,
                    'performance_status' => $row['performance_status'] ?? null,
                ]);
            }

            $this->auditService->log(AuditAction::Created, 'reports', Report::class, $report->id);

            return $this->load($report);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Report $report, array $data): Report
    {
        $this->assertOrg($report);
        $this->assertEditable($report);

        $report->fill(collect($data)->only([
            'title',
            'executive_summary',
            'performance_summary',
            'challenges',
            'risks',
            'recommendations',
            'starts_on',
            'ends_on',
            'reviewed_by',
        ])->all())->save();

        $this->auditService->log(AuditAction::Updated, 'reports', Report::class, $report->id);

        return $this->load($report->fresh());
    }

    public function submit(Report $report, ?string $comment = null): Report
    {
        $this->assertOrg($report);

        $result = $this->workflowService->transition(
            $report,
            ReportStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [ReportStatus::Draft->value, ReportStatus::Returned->value],
            $comment,
            'reports',
        );

        $result->submitted_at = now();
        $result->save();

        return $this->load($result->fresh());
    }

    public function returnForCorrection(Report $report, ?string $comment = null): Report
    {
        $this->assertOrg($report);

        return $this->load($this->workflowService->transition(
            $report,
            ReportStatus::Returned->value,
            ApprovalAction::Returned,
            [ReportStatus::UnderReview->value],
            $comment,
            'reports',
            true,
        ));
    }

    public function approve(Report $report, ?string $comment = null): Report
    {
        $this->assertOrg($report);

        $result = $this->workflowService->transition(
            $report,
            ReportStatus::Approved->value,
            ApprovalAction::Approved,
            [ReportStatus::UnderReview->value],
            $comment,
            'reports',
            false,
            true,
        );

        $result->reviewed_by = $result->reviewed_by ?? Auth::id();
        $result->save();

        return $this->load($result->fresh());
    }

    public function publish(Report $report, ?string $comment = null): Report
    {
        $this->assertOrg($report);

        $result = $this->workflowService->transition(
            $report,
            ReportStatus::Published->value,
            ApprovalAction::Published,
            [ReportStatus::Approved->value],
            $comment,
            'reports',
        );

        $result->published_at = now();
        $result->save();

        return $this->load($result->fresh());
    }

    public function exportPdf(Report $report): string
    {
        $this->assertOrg($report);

        return $this->reportExportService->toPdf($this->load($report));
    }

    public function exportExcel(Report $report): string
    {
        $this->assertOrg($report);

        return $this->reportExportService->toCsv($this->load($report));
    }

    /**
     * @param  array<string, mixed>  $dashboard
     */
    protected function draftSummary(array $dashboard, bool $aiLabel = false): string
    {
        $summary = $dashboard['summary'] ?? [];
        $text = sprintf(
            'Performance overview: %d projects (%d active), %d indicators on track, %d at risk, %d off track. Pending reports: %d.',
            (int) ($summary['total_projects'] ?? 0),
            (int) ($summary['active_projects'] ?? 0),
            (int) ($summary['indicators_on_track'] ?? 0),
            (int) ($summary['indicators_at_risk'] ?? 0),
            (int) ($summary['indicators_off_track'] ?? 0),
            (int) ($summary['pending_reports'] ?? 0),
        );

        return $aiLabel ? '[AI DRAFT] '.$text.' Requires human review before publication.' : $text;
    }

    protected function nextReference(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $count = Report::query()
            ->where('organization_id', $this->organizationContext->id())
            ->count() + 1;

        return sprintf('RPT-%s-%04d', $prefix, $count);
    }

    protected function assertEditable(Report $report): void
    {
        $status = $report->status instanceof ReportStatus ? $report->status->value : (string) $report->status;

        if (! in_array($status, [ReportStatus::Draft->value, ReportStatus::Returned->value], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only draft or returned reports can be edited.'],
            ]);
        }
    }

    protected function load(Report $report): Report
    {
        return $report->load(['indicatorResults', 'project', 'programme', 'reportingPeriod', 'approvalHistories', 'preparer']);
    }

    protected function assertOrg(Report $report): void
    {
        if ($report->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
