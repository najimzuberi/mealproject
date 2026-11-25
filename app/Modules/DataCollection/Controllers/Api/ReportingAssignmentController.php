<?php

namespace App\Modules\DataCollection\Controllers\Api;

use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\DataCollection\Resources\ReportingAssignmentResource;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingAssignmentController extends Controller
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReportingAssignment::class);

        $query = ReportingAssignment::query()
            ->with(['monitoringPlan', 'monitoringPlanItem', 'assignee'])
            ->where('organization_id', $this->organizationContext->id());

        if ($request->boolean('mine')) {
            $query->where('assigned_to', $request->user()?->id);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('monitoring_plan_id')) {
            $query->where('monitoring_plan_id', $planId);
        }

        return ApiResponse::success(ReportingAssignmentResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ReportingAssignment::class);

        $data = $request->validate([
            'monitoring_plan_item_id' => ['required', 'integer', 'exists:monitoring_plan_items,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_on' => ['nullable', 'date'],
        ]);

        $item = MonitoringPlanItem::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where('id', $data['monitoring_plan_item_id'])
            ->firstOrFail();

        $assignment = ReportingAssignment::query()->create([
            'organization_id' => $item->organization_id,
            'monitoring_plan_id' => $item->monitoring_plan_id,
            'monitoring_plan_item_id' => $item->id,
            'reporting_period_id' => $data['reporting_period_id'] ?? null,
            'assigned_to' => $data['assigned_to'],
            'supervisor_id' => $data['supervisor_id'] ?? $item->supervisor_id,
            'due_on' => $data['due_on'] ?? $item->collection_deadline,
            'status' => 'pending',
        ]);

        return ApiResponse::success(
            new ReportingAssignmentResource($assignment->load(['monitoringPlan', 'monitoringPlanItem', 'assignee'])),
            'Assignment created.',
            status: 201,
        );
    }

    public function show(ReportingAssignment $reportingAssignment): JsonResponse
    {
        $this->authorize('view', $reportingAssignment);

        if ($reportingAssignment->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        return ApiResponse::success(new ReportingAssignmentResource(
            $reportingAssignment->load(['monitoringPlan', 'monitoringPlanItem', 'assignee', 'submissions']),
        ));
    }
}
