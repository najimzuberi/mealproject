<?php

namespace App\Modules\Performance\Services;

use App\Enums\IndicatorStatus;
use App\Enums\ProjectStatus;
use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PerformanceAggregationService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * Official metrics use published submissions only (approved published pipeline).
     *
     * @return array<string, mixed>
     */
    public function dashboard(Request $request): array
    {
        $organizationId = $this->organizationContext->id();
        $filters = $this->filters($request);

        $projectsQuery = Project::query()->where('organization_id', $organizationId);
        if ($filters['programme_id']) {
            $projectsQuery->where('programme_id', $filters['programme_id']);
        }
        if ($filters['project_id']) {
            $projectsQuery->where('id', $filters['project_id']);
        }

        $projects = $projectsQuery->get();
        $projectIds = $projects->pluck('id');

        $indicatorsQuery = Indicator::query()
            ->where('organization_id', $organizationId)
            ->where('status', IndicatorStatus::Approved->value);
        if ($filters['project_id']) {
            $indicatorsQuery->where('project_id', $filters['project_id']);
        }
        if ($filters['indicator_id']) {
            $indicatorsQuery->where('id', $filters['indicator_id']);
        }

        $indicatorResults = $this->indicatorResults($filters, limit: 100);
        $statusCounts = collect($indicatorResults)->countBy('performance_status');

        $pendingReports = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->when($filters['reporting_period_id'], fn ($q) => $q->where('reporting_period_id', $filters['reporting_period_id']))
            ->where('status', 'pending')
            ->count();

        $reportingStatus = $this->reportingStatus($filters);

        return [
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'summary' => [
                'total_projects' => $projects->count(),
                'active_projects' => $projects->filter(function ($p) {
                    $status = $p->status instanceof ProjectStatus ? $p->status->value : (string) $p->status;

                    return $status === ProjectStatus::Active->value;
                })->count(),
                'completed_projects' => $projects->filter(function ($p) {
                    $status = $p->status instanceof ProjectStatus ? $p->status->value : (string) $p->status;

                    return $status === ProjectStatus::Completed->value;
                })->count(),
                'total_indicators' => (clone $indicatorsQuery)->count(),
                'indicators_on_track' => $statusCounts->get('on_track', 0),
                'indicators_at_risk' => $statusCounts->get('at_risk', 0),
                'indicators_off_track' => $statusCounts->get('off_track', 0),
                'pending_reports' => $pendingReports,
            ],
            'project_performance' => $this->projectPerformance($projects, $indicatorResults),
            'indicator_performance' => $indicatorResults,
            'target_vs_achievement' => $this->targetVsAchievement($indicatorResults, $filters),
            'trends' => $this->trends($filters),
            'reporting_status' => $reportingStatus,
            'attention_projects' => $this->attentionProjects($projects, $indicatorResults, $reportingStatus),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function indicatorResults(array $filters, ?int $limit = null): array
    {
        $organizationId = $this->organizationContext->id();

        $items = MonitoringPlanItem::query()
            ->where('organization_id', $organizationId)
            ->with(['indicator.baselines', 'indicator.targets', 'monitoringPlan'])
            ->when($filters['project_id'], function ($q) use ($filters) {
                $q->whereHas('monitoringPlan', fn ($mq) => $mq->where('project_id', $filters['project_id']));
            })
            ->when($filters['programme_id'], function ($q) use ($filters) {
                $q->whereHas('monitoringPlan.project', fn ($pq) => $pq->where('programme_id', $filters['programme_id']));
            })
            ->when($filters['indicator_id'], fn ($q) => $q->where('indicator_id', $filters['indicator_id']))
            ->get();

        $submissions = Submission::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', [SubmissionStatus::Published->value, SubmissionStatus::Locked->value])
            ->when($filters['reporting_period_id'], fn ($q) => $q->where('reporting_period_id', $filters['reporting_period_id']))
            ->when($filters['project_id'], function ($q) use ($filters) {
                $q->whereHas('monitoringPlan', fn ($mq) => $mq->where('project_id', $filters['project_id']));
            })
            ->get()
            ->groupBy('monitoring_plan_item_id');

        $rows = [];

        foreach ($items as $item) {
            $indicator = $item->indicator;
            if (! $indicator) {
                continue;
            }

            $itemSubs = $submissions->get($item->id, collect());
            $actual = $itemSubs->avg(fn (Submission $s) => (float) $s->value);
            $baseline = $item->baseline_value !== null
                ? (float) $item->baseline_value
                : (float) ($indicator->baselines->first()?->value ?? 0);
            $target = $item->target_value !== null
                ? (float) $item->target_value
                : (float) ($indicator->targets->first()?->value ?? 0);

            $achievement = $target != 0.0 ? round(($actual / $target) * 100, 2) : null;
            $variance = $actual !== null ? round($actual - $target, 4) : null;
            $status = $this->performanceStatus($achievement, $indicator->direction_of_change);

            $period = null;
            if ($filters['reporting_period_id']) {
                $period = ReportingPeriod::query()->find($filters['reporting_period_id']);
            } elseif ($itemSubs->isNotEmpty()) {
                $period = ReportingPeriod::query()->find($itemSubs->first()->reporting_period_id);
            }

            $rows[] = [
                'indicator_id' => $indicator->id,
                'indicator_code' => $indicator->code,
                'indicator_name' => $indicator->name,
                'project_id' => $item->monitoringPlan?->project_id,
                'monitoring_plan_item_id' => $item->id,
                'baseline' => $baseline,
                'target' => $target,
                'actual' => $actual !== null ? round((float) $actual, 4) : null,
                'achievement_percent' => $achievement,
                'variance' => $variance,
                'reporting_period_id' => $period?->id,
                'reporting_period' => $period?->name,
                'performance_status' => $status,
                'direction_of_change' => $indicator->direction_of_change,
            ];
        }

        if ($limit !== null) {
            return array_slice($rows, 0, $limit);
        }

        return $rows;
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function filters(Request $request): array
    {
        return [
            'programme_id' => $request->integer('programme_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'reporting_period_id' => $request->integer('reporting_period_id') ?: null,
            'indicator_id' => $request->integer('indicator_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'office_id' => $request->integer('office_id') ?: null,
            'facility_id' => $request->integer('facility_id') ?: null,
            'geographic_unit_id' => $request->integer('geographic_unit_id') ?: null,
            'sector_id' => $request->integer('sector_id') ?: null,
            'granularity' => $request->string('granularity', 'quarterly')->toString(),
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  list<array<string, mixed>>  $indicatorResults
     * @return list<array<string, mixed>>
     */
    protected function projectPerformance(Collection $projects, array $indicatorResults): array
    {
        $byProject = collect($indicatorResults)->groupBy('project_id');

        return $projects->map(function (Project $project) use ($byProject) {
            $rows = $byProject->get($project->id, collect());
            $avgAchievement = $rows->avg('achievement_percent');
            $status = $project->status instanceof ProjectStatus ? $project->status->value : (string) $project->status;

            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_code' => $project->code ?? null,
                'status' => $status,
                'planned_progress' => 100,
                'actual_progress' => $avgAchievement !== null ? round((float) $avgAchievement, 1) : null,
                'completion_percent' => $avgAchievement !== null ? round((float) $avgAchievement, 1) : null,
                'performance_status' => $this->performanceStatus($avgAchievement !== null ? (float) $avgAchievement : null),
            ];
        })->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $indicatorResults
     * @param  array<string, mixed>  $filters
     * @return array{labels: list<string>, targets: list<float|null>, actuals: list<float|null>}
     */
    protected function targetVsAchievement(array $indicatorResults, array $filters): array
    {
        $slice = array_slice($indicatorResults, 0, 12);

        return [
            'granularity' => $filters['granularity'],
            'labels' => array_map(fn ($r) => $r['indicator_code'] ?? 'IND', $slice),
            'targets' => array_map(fn ($r) => $r['target'], $slice),
            'actuals' => array_map(fn ($r) => $r['actual'], $slice),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    protected function trends(array $filters): array
    {
        $organizationId = $this->organizationContext->id();

        $periods = ReportingPeriod::query()
            ->where('organization_id', $organizationId)
            ->orderBy('starts_on')
            ->limit(12)
            ->get();

        return $periods->map(function (ReportingPeriod $period) use ($organizationId, $filters) {
            $avg = Submission::query()
                ->where('organization_id', $organizationId)
                ->where('reporting_period_id', $period->id)
                ->whereIn('status', [SubmissionStatus::Published->value, SubmissionStatus::Locked->value])
                ->when($filters['project_id'], function ($q) use ($filters) {
                    $q->whereHas('monitoringPlan', fn ($mq) => $mq->where('project_id', $filters['project_id']));
                })
                ->avg('value');

            return [
                'reporting_period_id' => $period->id,
                'label' => $period->name,
                'starts_on' => $period->starts_on?->toDateString(),
                'average_actual' => $avg !== null ? round((float) $avg, 2) : null,
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function reportingStatus(array $filters): array
    {
        $organizationId = $this->organizationContext->id();

        $base = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->when($filters['reporting_period_id'], fn ($q) => $q->where('reporting_period_id', $filters['reporting_period_id']));

        $pending = (clone $base)->where('status', 'pending')->count();
        $submitted = (clone $base)->where('status', 'submitted')->count();
        $overdue = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', now()->toDateString())
            ->count();

        $approved = Submission::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', [
                SubmissionStatus::Approved->value,
                SubmissionStatus::Published->value,
                SubmissionStatus::Locked->value,
            ])
            ->when($filters['reporting_period_id'], fn ($q) => $q->where('reporting_period_id', $filters['reporting_period_id']))
            ->count();

        $total = (clone $base)->count();

        return [
            'submitted' => $submitted,
            'pending' => $pending,
            'overdue' => $overdue,
            'approved' => $approved,
            'completion_percent' => $total > 0 ? round((($submitted + $approved) / max($total, 1)) * 100, 1) : 0,
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  list<array<string, mixed>>  $indicatorResults
     * @param  array<string, mixed>  $reportingStatus
     * @return list<array<string, mixed>>
     */
    protected function attentionProjects(Collection $projects, array $indicatorResults, array $reportingStatus): array
    {
        $byProject = collect($indicatorResults)->groupBy('project_id');
        $attention = [];

        foreach ($projects as $project) {
            $rows = $byProject->get($project->id, collect());
            $offTrack = $rows->where('performance_status', 'off_track')->count();
            $atRisk = $rows->where('performance_status', 'at_risk')->count();
            $reasons = [];

            if ($offTrack > 0) {
                $reasons[] = 'missed_targets';
            }
            if ($atRisk > 0) {
                $reasons[] = 'performance_gaps';
            }

            $status = $project->status instanceof ProjectStatus ? $project->status->value : (string) $project->status;
            if ($status === ProjectStatus::OnHold->value) {
                $reasons[] = 'delayed_implementation';
            }

            if ($reasons === []) {
                continue;
            }

            $attention[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'reasons' => $reasons,
                'off_track_indicators' => $offTrack,
                'at_risk_indicators' => $atRisk,
                'overdue_submissions' => $reportingStatus['overdue'] ?? 0,
            ];
        }

        return array_slice($attention, 0, 20);
    }

    protected function performanceStatus(?float $achievementPercent, ?string $direction = null): string
    {
        if ($achievementPercent === null) {
            return 'no_data';
        }

        $score = $achievementPercent;
        if ($direction === 'decrease') {
            // For decrease-is-better, treat lower achievement of "bad" as better when actual < target
            // Achievement already computed as actual/target; invert bands softly.
            if ($score <= 100) {
                return 'on_track';
            }
            if ($score <= 120) {
                return 'at_risk';
            }

            return 'off_track';
        }

        if ($score >= 90) {
            return 'on_track';
        }
        if ($score >= 70) {
            return 'at_risk';
        }

        return 'off_track';
    }
}
