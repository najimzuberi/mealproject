<?php

namespace App\Modules\Indicator\Controllers\Api;

use App\Modules\Indicator\Models\Indicator;
use App\Modules\Indicator\Resources\IndicatorResource;
use App\Modules\Indicator\Services\IndicatorService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function __construct(
        protected IndicatorService $indicatorService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Indicator::class);

        $query = Indicator::query()->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($level = $request->string('result_level')->toString()) {
            $query->where('result_level', $level);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['created_at', 'name', 'code', 'status', 'version'], true)) {
            $query->orderBy($sort, $direction);
        }

        return ApiResponse::success(IndicatorResource::collection(
            $query->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Indicator::class);

        $data = $request->validate($this->rules());

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->create($data)),
            'Indicator created.',
            status: 201,
        );
    }

    public function show(Indicator $indicator): JsonResponse
    {
        $this->authorize('view', $indicator);

        return ApiResponse::success(new IndicatorResource(
            $indicator->load(['baselines', 'targets', 'disaggregations', 'approvalHistories']),
        ));
    }

    public function update(Request $request, Indicator $indicator): JsonResponse
    {
        $this->authorize('update', $indicator);

        $data = $request->validate($this->rules(false));

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->update($indicator, $data)),
            'Indicator updated.',
        );
    }

    public function submit(WorkflowCommentRequest $request, Indicator $indicator): JsonResponse
    {
        $this->authorize('submit', $indicator);

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->submit($indicator, $request->validated('comment'))),
            'Indicator submitted.',
        );
    }

    public function returnIndicator(WorkflowCommentRequest $request, Indicator $indicator): JsonResponse
    {
        $this->authorize('approve', $indicator);

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->returnForCorrection($indicator, $request->validated('comment'))),
            'Indicator returned for correction.',
        );
    }

    public function approve(WorkflowCommentRequest $request, Indicator $indicator): JsonResponse
    {
        $this->authorize('approve', $indicator);

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->approve($indicator, $request->validated('comment'))),
            'Indicator approved.',
        );
    }

    public function retire(WorkflowCommentRequest $request, Indicator $indicator): JsonResponse
    {
        $this->authorize('manage', $indicator);

        return ApiResponse::success(
            new IndicatorResource($this->indicatorService->retire($indicator, $request->validated('comment'))),
            'Indicator retired.',
        );
    }

    public function versions(Indicator $indicator): JsonResponse
    {
        $this->authorize('view', $indicator);

        return ApiResponse::success(
            IndicatorResource::collection(collect($this->indicatorService->versionHistory($indicator))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $creating = true): array
    {
        return [
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'max:50'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'definition' => ['nullable', 'string'],
            'measurement_unit' => ['nullable', 'string', 'max:100'],
            'numerator' => ['nullable', 'string'],
            'denominator' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'aggregation_method' => ['nullable', 'string', 'max:100'],
            'direction_of_change' => ['nullable', 'in:increase,decrease,maintain'],
            'result_level' => ['nullable', 'in:impact,outcome,output,process,activity'],
            'strategic_objective_id' => ['nullable', 'integer', 'exists:strategic_objectives,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'results_framework_id' => ['nullable', 'integer', 'exists:results_frameworks,id'],
            'result_id' => ['nullable', 'integer', 'exists:results,id'],
            'indicator_type' => ['nullable', 'in:organizational,donor,government,project'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_organization' => ['nullable', 'string', 'max:255'],
            'source_accessed_on' => ['nullable', 'date'],
            'adaptation_notes' => ['nullable', 'string'],
            'data_source' => ['nullable', 'string', 'max:255'],
            'collection_method' => ['nullable', 'string', 'max:255'],
            'collection_frequency' => ['nullable', 'string', 'max:100'],
            'responsible_collector_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'me_validator_id' => ['nullable', 'integer', 'exists:users,id'],
            'means_of_verification' => ['nullable', 'string'],
            'validation_rules' => ['nullable', 'string'],
            'limitations' => ['nullable', 'string'],
            'assumptions' => ['nullable', 'string'],
            'quality_checks' => ['nullable', 'string'],
            'baselines' => ['sometimes', 'array'],
            'baselines.*.value' => ['required', 'string'],
            'baselines.*.baseline_date' => ['nullable', 'date'],
            'targets' => ['sometimes', 'array'],
            'targets.*.value' => ['required', 'string'],
            'targets.*.is_cumulative' => ['sometimes', 'boolean'],
            'disaggregations' => ['sometimes', 'array'],
            'disaggregations.*.dimension' => ['required', 'in:sex,age,disability,geography,custom'],
            'disaggregations.*.label' => ['nullable', 'string', 'max:255'],
            'disaggregations.*.is_required' => ['sometimes', 'boolean'],
        ];
    }
}
