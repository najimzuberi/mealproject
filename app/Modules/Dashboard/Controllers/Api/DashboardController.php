<?php

namespace App\Modules\Dashboard\Controllers\Api;

use App\Enums\IndicatorStatus;
use App\Enums\MonitoringPlanStatus;
use App\Enums\ProjectStatus;
use App\Enums\StrategicPlanStatus;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Performance\Services\PerformanceAggregationService;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected PerformanceAggregationService $performanceAggregationService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $organizationId = $this->organizationContext->id();

        $projectStatus = Project::query()
            ->where('organization_id', $organizationId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingApprovals = 0;
        if (auth()->user()?->can('strategic_plans.view')
            || auth()->user()?->can('annual_work_plans.view')
            || auth()->user()?->can('indicators.view')
            || auth()->user()?->can('monitoring_plans.view')) {
            $pendingApprovals += StrategicPlan::query()
                ->where('organization_id', $organizationId)
                ->where('status', StrategicPlanStatus::UnderReview->value)
                ->count();
            $pendingApprovals += AnnualWorkPlan::query()
                ->where('organization_id', $organizationId)
                ->where('status', StrategicPlanStatus::UnderReview->value)
                ->count();
            $pendingApprovals += Indicator::query()
                ->where('organization_id', $organizationId)
                ->where('status', IndicatorStatus::UnderReview->value)
                ->count();
            $pendingApprovals += MonitoringPlan::query()
                ->where('organization_id', $organizationId)
                ->where('status', MonitoringPlanStatus::UnderReview->value)
                ->count();
        }

        $kpis = [
            'total_projects' => Project::query()->where('organization_id', $organizationId)->count(),
            'active_projects' => Project::query()
                ->where('organization_id', $organizationId)
                ->where('status', ProjectStatus::Active->value)
                ->count(),
            'strategic_plans' => StrategicPlan::query()->where('organization_id', $organizationId)->count(),
            'active_indicators' => Indicator::query()
                ->where('organization_id', $organizationId)
                ->where('status', IndicatorStatus::Approved->value)
                ->count(),
            'monitoring_plans' => MonitoringPlan::query()->where('organization_id', $organizationId)->count(),
            'pending_approvals' => $pendingApprovals,
        ];

        $reportingPeriods = ReportingPeriod::query()
            ->where('organization_id', $organizationId)
            ->with('reportingCalendar:id,name')
            ->orderByDesc('ends_on')
            ->limit(8)
            ->get()
            ->map(fn (ReportingPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'status' => $period->status,
                'starts_on' => $period->starts_on?->toDateString(),
                'ends_on' => $period->ends_on?->toDateString(),
                'calendar' => $period->reportingCalendar?->name,
            ]);

        $recentActivities = [];
        if (auth()->user()?->can('audit.view')) {
            $recentActivities = AuditLog::query()
                ->where('organization_id', $organizationId)
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'module' => $log->module,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->all();
        }

        $pendingTasks = [];
        if (auth()->user()?->can('strategic_plans.approve')) {
            StrategicPlan::query()
                ->where('organization_id', $organizationId)
                ->where('status', StrategicPlanStatus::UnderReview->value)
                ->limit(5)
                ->get()
                ->each(function (StrategicPlan $plan) use (&$pendingTasks) {
                    $pendingTasks[] = [
                        'type' => 'strategic_plan',
                        'id' => $plan->id,
                        'title' => $plan->title,
                        'status' => $plan->status instanceof \BackedEnum ? $plan->status->value : $plan->status,
                        'route' => '/app/strategic-plans',
                    ];
                });
        }

        $performance = $this->performanceAggregationService->dashboard($request);

        return ApiResponse::success([
            'organization_id' => $organizationId,
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'project_status' => $projectStatus,
            'reporting_periods' => $reportingPeriods,
            'pending_tasks' => $pendingTasks,
            'recent_activities' => $recentActivities,
            'objectives_progress' => [
                'available' => true,
                'message' => null,
                'items' => array_slice($performance['project_performance'] ?? [], 0, 8),
            ],
            'indicator_performance' => [
                'available' => true,
                'message' => null,
                'items' => array_slice($performance['indicator_performance'] ?? [], 0, 8),
            ],
        ]);
    }
}
