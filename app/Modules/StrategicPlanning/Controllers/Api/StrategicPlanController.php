<?php

namespace App\Modules\StrategicPlanning\Controllers\Api;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Modules\StrategicPlanning\Requests\ReturnStrategicPlanRequest;
use App\Modules\StrategicPlanning\Requests\StoreStrategicPlanDocumentRequest;
use App\Modules\StrategicPlanning\Requests\StoreStrategicPlanRequest;
use App\Modules\StrategicPlanning\Requests\UpdateStrategicPlanRequest;
use App\Modules\StrategicPlanning\Resources\StrategicPlanResource;
use App\Modules\StrategicPlanning\Services\StrategicPlanService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Requests\WorkflowCommentRequest;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StrategicPlanController extends Controller
{
    public function __construct(
        protected StrategicPlanService $strategicPlanService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StrategicPlan::class);

        $plans = StrategicPlan::query()
            ->where('organization_id', $this->organizationContext->id())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('department_id'), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->date('start_date'), fn ($query, $start) => $query->whereDate('start_date', '>=', $start))
            ->when($request->date('end_date'), fn ($query, $end) => $query->whereDate('end_date', '<=', $end))
            ->with(['themes', 'priorities', 'goals.objectives.keyResultAreas', 'owner', 'department'])
            ->latest('updated_at')
            ->paginate();

        return ApiResponse::success(StrategicPlanResource::collection($plans));
    }

    public function store(StoreStrategicPlanRequest $request): JsonResponse
    {
        $this->authorize('create', StrategicPlan::class);

        $plan = $this->strategicPlanService->create($request->validated());

        return ApiResponse::success(new StrategicPlanResource($plan), status: 201);
    }

    public function show(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('view', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->loadFullStructure($strategicPlan)),
        );
    }

    public function update(UpdateStrategicPlanRequest $request, StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('update', $strategicPlan);

        return ApiResponse::success(new StrategicPlanResource(
            $this->strategicPlanService->update($strategicPlan, $request->validated()),
        ));
    }

    public function destroy(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('delete', $strategicPlan);

        $this->strategicPlanService->delete($strategicPlan);

        return ApiResponse::success(null, 'Strategic plan deleted.');
    }

    public function submit(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('submit', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->submit($strategicPlan)),
            'Strategic plan submitted for review.',
        );
    }

    public function returnForCorrection(ReturnStrategicPlanRequest $request, StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('review', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource(
                $this->strategicPlanService->returnForCorrection(
                    $strategicPlan,
                    (string) $request->validated('comment'),
                ),
            ),
            'Strategic plan returned for correction.',
        );
    }

    public function approve(WorkflowCommentRequest $request, StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('approve', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->approve($strategicPlan, $request->validated('comment'))),
            'Strategic plan approved.',
        );
    }

    public function activate(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('activate', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->activate($strategicPlan)),
            'Strategic plan activated.',
        );
    }

    public function archive(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('archive', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->archive($strategicPlan)),
            'Strategic plan archived.',
        );
    }

    public function duplicate(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('create', StrategicPlan::class);
        $this->authorize('view', $strategicPlan);

        return ApiResponse::success(
            new StrategicPlanResource($this->strategicPlanService->duplicate($strategicPlan)),
            'Strategic plan duplicated.',
            status: 201,
        );
    }

    public function versions(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('view', $strategicPlan);

        return ApiResponse::success(
            StrategicPlanResource::collection($this->strategicPlanService->versions($strategicPlan)),
        );
    }

    public function hierarchy(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('view', $strategicPlan);

        $plan = $this->strategicPlanService->loadFullStructure($strategicPlan);

        return ApiResponse::success([
            'id' => $plan->id,
            'code' => $plan->code,
            'title' => $plan->title,
            'status' => $plan->status,
            'goals' => $plan->goals->map(fn ($goal) => [
                'id' => $goal->id,
                'code' => $goal->code,
                'title' => $goal->title,
                'objectives' => $goal->objectives->map(fn ($objective) => [
                    'id' => $objective->id,
                    'code' => $objective->code,
                    'title' => $objective->title,
                    'kra' => $objective->keyResultAreas->map(fn ($kra) => [
                        'id' => $kra->id,
                        'code' => $kra->code,
                        'title' => $kra->title,
                    ]),
                    'outcomes' => $objective->outcomes->map(fn ($outcome) => [
                        'id' => $outcome->id,
                        'code' => $outcome->code,
                        'title' => $outcome->title,
                    ]),
                    'indicators' => $objective->indicators->map(fn ($indicator) => [
                        'id' => $indicator->id,
                        'code' => $indicator->code,
                        'name' => $indicator->name,
                    ]),
                ]),
            ]),
        ]);
    }

    public function storeDocument(StoreStrategicPlanDocumentRequest $request, StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('update', $strategicPlan);

        $document = $this->strategicPlanService->storeDocument(
            $strategicPlan,
            $request->file('file'),
            $request->validated('title'),
        );

        return ApiResponse::success(new DocumentResource($document), status: 201);
    }

    public function destroyDocument(StrategicPlan $strategicPlan, Document $document): JsonResponse
    {
        $this->authorize('update', $strategicPlan);

        $this->strategicPlanService->deleteDocument($strategicPlan, $document);

        return ApiResponse::success(null, 'Document deleted.');
    }
}
