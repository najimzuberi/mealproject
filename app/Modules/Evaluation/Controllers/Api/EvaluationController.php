<?php

namespace App\Modules\Evaluation\Controllers\Api;

use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Models\EvaluationRecommendation;
use App\Modules\Evaluation\Resources\EvaluationResource;
use App\Modules\Evaluation\Services\EvaluationService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(
        protected EvaluationService $evaluationService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Evaluation::class);
        $query = Evaluation::query()->where('organization_id', $this->organizationContext->id());
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        return ApiResponse::success(EvaluationResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Evaluation::class);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'evaluation_type' => ['nullable', 'string', 'max:100'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'purpose' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'evaluation_lead_id' => ['nullable', 'integer', 'exists:users,id'],
            'evaluation_team' => ['nullable', 'array'],
            'methodology' => ['nullable', 'string'],
            'data_collection_methods' => ['nullable', 'string'],
            'target_population' => ['nullable', 'string', 'max:255'],
            'sample_size' => ['nullable', 'integer', 'min:0'],
            'geographic_coverage' => ['nullable', 'string', 'max:255'],
            'tools' => ['nullable', 'string'],
            'timeline_notes' => ['nullable', 'string'],
            'questions' => ['sometimes', 'array'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.criterion' => ['nullable', 'string', 'max:255'],
        ]);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->create($data)), 'Evaluation created.', status: 201);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        $this->authorize('view', $evaluation);

        return ApiResponse::success(new EvaluationResource(
            $evaluation->load(['questions', 'findings', 'recommendations.actions', 'approvalHistories', 'project', 'programme']),
        ));
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('update', $evaluation);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'evaluation_type' => ['nullable', 'string', 'max:100'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'purpose' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'evaluation_lead_id' => ['nullable', 'integer', 'exists:users,id'],
            'evaluation_team' => ['nullable', 'array'],
            'methodology' => ['nullable', 'string'],
            'data_collection_methods' => ['nullable', 'string'],
            'target_population' => ['nullable', 'string', 'max:255'],
            'sample_size' => ['nullable', 'integer', 'min:0'],
            'geographic_coverage' => ['nullable', 'string', 'max:255'],
            'tools' => ['nullable', 'string'],
            'timeline_notes' => ['nullable', 'string'],
            'questions' => ['sometimes', 'array'],
            'questions.*.question' => ['required', 'string'],
        ]);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->update($evaluation, $data)), 'Evaluation updated.');
    }

    public function submit(WorkflowCommentRequest $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('submit', $evaluation);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->submit($evaluation, $request->validated('comment'))), 'Evaluation submitted.');
    }

    public function returnEvaluation(WorkflowCommentRequest $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('approve', $evaluation);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->returnForCorrection($evaluation, $request->validated('comment'))), 'Evaluation returned.');
    }

    public function approve(WorkflowCommentRequest $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('approve', $evaluation);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->approve($evaluation, $request->validated('comment'))), 'Evaluation approved.');
    }

    public function complete(WorkflowCommentRequest $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('manage', $evaluation);

        return ApiResponse::success(new EvaluationResource($this->evaluationService->complete($evaluation, $request->validated('comment'))), 'Evaluation completed.');
    }

    public function storeFinding(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('update', $evaluation);
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'supporting_evidence' => ['nullable', 'string'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'result_id' => ['nullable', 'integer', 'exists:results,id'],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
            'conclusion' => ['nullable', 'string'],
            'significance' => ['nullable', 'string', 'max:100'],
        ]);

        return ApiResponse::success($this->evaluationService->addFinding($evaluation, $data), 'Finding added.', status: 201);
    }

    public function storeRecommendation(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('update', $evaluation);
        $data = $request->validate([
            'evaluation_finding_id' => ['nullable', 'integer', 'exists:evaluation_findings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'supporting_evidence' => ['nullable', 'string'],
            'responsible_department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        return ApiResponse::success($this->evaluationService->addRecommendation($evaluation, $data), 'Recommendation added.', status: 201);
    }

    public function respondRecommendation(Request $request, EvaluationRecommendation $evaluationRecommendation): JsonResponse
    {
        $evaluationRecommendation->loadMissing('evaluation');
        $this->authorize('approve', $evaluationRecommendation->evaluation);
        $data = $request->validate([
            'management_response_status' => ['required', 'in:accepted,partially_accepted,rejected'],
            'management_response' => ['nullable', 'string'],
            'response_date' => ['nullable', 'date'],
            'responsible_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_description' => ['nullable', 'string'],
            'responsible_person_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_on' => ['nullable', 'date'],
            'required_evidence' => ['nullable', 'string'],
        ]);

        return ApiResponse::success($this->evaluationService->respondToRecommendation($evaluationRecommendation, $data), 'Management response recorded.');
    }
}
