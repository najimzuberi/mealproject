<?php

namespace App\Modules\MonitoringPlan\Controllers\Api;

use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Resources\MonitoringPlanResource;
use App\Modules\MonitoringPlan\Services\MonitoringPlanService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringPlanController extends Controller
{
    public function __construct(
        protected MonitoringPlanService $monitoringPlanService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPlan::class);

        $query = MonitoringPlan::query()->where('organization_id', $this->organizationContext->id());

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        return ApiResponse::success(MonitoringPlanResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MonitoringPlan::class);

        $data = $request->validate($this->rules());

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->create($data)),
            'Monitoring plan created.',
            status: 201,
        );
    }

    public function show(MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('view', $monitoringPlan);

        return ApiResponse::success(new MonitoringPlanResource(
            $monitoringPlan->load(['project', 'items.indicator', 'approvalHistories']),
        ));
    }

    public function update(Request $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('update', $monitoringPlan);

        $data = $request->validate($this->rules(false));

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->update($monitoringPlan, $data)),
            'Monitoring plan updated.',
        );
    }

    public function submit(WorkflowCommentRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('submit', $monitoringPlan);

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->submit($monitoringPlan, $request->validated('comment'))),
            'Monitoring plan submitted.',
        );
    }

    public function returnPlan(WorkflowCommentRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('review', $monitoringPlan);

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->returnForCorrection($monitoringPlan, $request->validated('comment'))),
            'Monitoring plan returned for correction.',
        );
    }

    public function approve(WorkflowCommentRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('approve', $monitoringPlan);

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->approve($monitoringPlan, $request->validated('comment'))),
            'Monitoring plan approved.',
        );
    }

    public function activate(WorkflowCommentRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('manage', $monitoringPlan);

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->activate($monitoringPlan, $request->validated('comment'))),
            'Monitoring plan activated.',
        );
    }

    public function archive(WorkflowCommentRequest $request, MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('manage', $monitoringPlan);

        return ApiResponse::success(
            new MonitoringPlanResource($this->monitoringPlanService->archive($monitoringPlan, $request->validated('comment'))),
            'Monitoring plan archived.',
        );
    }

    public function versions(MonitoringPlan $monitoringPlan): JsonResponse
    {
        $this->authorize('view', $monitoringPlan);

        return ApiResponse::success(
            MonitoringPlanResource::collection(collect($this->monitoringPlanService->versionHistory($monitoringPlan))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $creating = true): array
    {
        return [
            'project_id' => [$creating ? 'required' : 'prohibited', 'integer', 'exists:projects,id'],
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'max:50'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'intended_use' => ['nullable', 'string'],
            'assumptions' => ['nullable', 'string'],
            'limitations' => ['nullable', 'string'],
            'items' => ['sometimes', 'array'],
            'items.*.indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'items.*.baseline_value' => ['nullable', 'string'],
            'items.*.target_value' => ['nullable', 'string'],
            'items.*.data_source' => ['nullable', 'string'],
            'items.*.collection_method' => ['nullable', 'string'],
            'items.*.collection_frequency' => ['nullable', 'string'],
            'items.*.collection_deadline' => ['nullable', 'date'],
            'items.*.responsible_collector_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.me_validator_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.means_of_verification' => ['nullable', 'string'],
            'items.*.validation_rules' => ['nullable', 'string'],
            'items.*.reporting_format' => ['nullable', 'string'],
            'items.*.intended_use' => ['nullable', 'string'],
            'items.*.assumptions' => ['nullable', 'string'],
            'items.*.limitations' => ['nullable', 'string'],
        ];
    }
}
