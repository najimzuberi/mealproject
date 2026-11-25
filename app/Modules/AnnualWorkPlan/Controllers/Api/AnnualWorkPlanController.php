<?php

namespace App\Modules\AnnualWorkPlan\Controllers\Api;

use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\AnnualWorkPlan\Requests\ReturnAnnualWorkPlanRequest;
use App\Modules\AnnualWorkPlan\Requests\StoreAnnualWorkPlanRequest;
use App\Modules\AnnualWorkPlan\Requests\SyncAwpBudgetLinesRequest;
use App\Modules\AnnualWorkPlan\Requests\UpdateAnnualWorkPlanRequest;
use App\Modules\AnnualWorkPlan\Resources\AnnualWorkPlanResource;
use App\Modules\AnnualWorkPlan\Services\AnnualWorkPlanService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Requests\WorkflowCommentRequest;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnualWorkPlanController extends Controller
{
    public function __construct(
        protected AnnualWorkPlanService $annualWorkPlanService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AnnualWorkPlan::class);

        $items = AnnualWorkPlan::query()
            ->where('organization_id', $this->organizationContext->id())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('strategic_plan_id'), fn ($query, $planId) => $query->where('strategic_plan_id', $planId))
            ->when($request->integer('planning_year'), fn ($query, $year) => $query->where('planning_year', $year))
            ->with(['priorities.activities.milestones', 'strategicPlan'])
            ->latest('updated_at')
            ->paginate();

        return ApiResponse::success(AnnualWorkPlanResource::collection($items));
    }

    public function store(StoreAnnualWorkPlanRequest $request): JsonResponse
    {
        $this->authorize('create', AnnualWorkPlan::class);

        $awp = $this->annualWorkPlanService->create($request->validated());

        return ApiResponse::success(new AnnualWorkPlanResource($awp), status: 201);
    }

    public function show(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('view', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource($this->annualWorkPlanService->loadFullStructure($annualWorkPlan)),
        );
    }

    public function update(UpdateAnnualWorkPlanRequest $request, AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('update', $annualWorkPlan);

        return ApiResponse::success(new AnnualWorkPlanResource(
            $this->annualWorkPlanService->update($annualWorkPlan, $request->validated()),
        ));
    }

    public function destroy(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('delete', $annualWorkPlan);

        $this->annualWorkPlanService->delete($annualWorkPlan);

        return ApiResponse::success(null, 'Annual work plan deleted.');
    }

    public function submit(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('submit', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource($this->annualWorkPlanService->submit($annualWorkPlan)),
            'Annual work plan submitted for review.',
        );
    }

    public function returnForCorrection(ReturnAnnualWorkPlanRequest $request, AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('review', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource(
                $this->annualWorkPlanService->returnForCorrection(
                    $annualWorkPlan,
                    (string) $request->validated('comment'),
                ),
            ),
            'Annual work plan returned for correction.',
        );
    }

    public function approve(WorkflowCommentRequest $request, AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('approve', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource($this->annualWorkPlanService->approve($annualWorkPlan, $request->validated('comment'))),
            'Annual work plan approved.',
        );
    }

    public function activate(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('activate', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource($this->annualWorkPlanService->activate($annualWorkPlan)),
            'Annual work plan activated.',
        );
    }

    public function archive(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('archive', $annualWorkPlan);

        return ApiResponse::success(
            new AnnualWorkPlanResource($this->annualWorkPlanService->archive($annualWorkPlan)),
            'Annual work plan archived.',
        );
    }

    public function syncBudgetLines(SyncAwpBudgetLinesRequest $request, AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('update', $annualWorkPlan);

        return ApiResponse::success(new AnnualWorkPlanResource(
            $this->annualWorkPlanService->syncBudgetLines($annualWorkPlan, $request->validated('budget_lines')),
        ));
    }

    public function budgetSummary(AnnualWorkPlan $annualWorkPlan): JsonResponse
    {
        $this->authorize('view', $annualWorkPlan);

        return ApiResponse::success($this->annualWorkPlanService->budgetSummary($annualWorkPlan));
    }
}
