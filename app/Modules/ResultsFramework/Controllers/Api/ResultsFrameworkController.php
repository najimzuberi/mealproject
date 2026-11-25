<?php

namespace App\Modules\ResultsFramework\Controllers\Api;

use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\ResultsFramework\Resources\ResultsFrameworkResource;
use App\Modules\ResultsFramework\Services\ResultsFrameworkService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Requests\WorkflowCommentRequest;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultsFrameworkController extends Controller
{
    public function __construct(
        protected ResultsFrameworkService $resultsFrameworkService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ResultsFramework::class);

        $query = ResultsFramework::query()->where('organization_id', $this->organizationContext->id());

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        return ApiResponse::success(ResultsFrameworkResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ResultsFramework::class);

        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'theory_of_change' => ['nullable', 'string'],
            'results' => ['sometimes', 'array'],
            'results.*.level' => ['required', 'in:impact,outcome,output,process,activity'],
            'results.*.title' => ['required', 'string', 'max:255'],
            'results.*.code' => ['nullable', 'string', 'max:50'],
            'results.*.description' => ['nullable', 'string'],
            'results.*.children' => ['sometimes', 'array'],
        ]);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->create($data)),
            'Results framework created.',
            status: 201,
        );
    }

    public function show(ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('view', $resultsFramework);

        return ApiResponse::success(new ResultsFrameworkResource(
            $resultsFramework->load(['project', 'results.assumptions', 'results.risks']),
        ));
    }

    public function update(Request $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('update', $resultsFramework);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'theory_of_change' => ['nullable', 'string'],
            'results' => ['sometimes', 'array'],
        ]);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->update($resultsFramework, $data)),
            'Results framework updated.',
        );
    }

    public function hierarchy(ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('view', $resultsFramework);

        $payload = $this->resultsFrameworkService->hierarchy($resultsFramework);

        // Frontend tree root nodes at data[] plus framework metadata for diagram tooling.
        return ApiResponse::success($payload['tree'], meta: [
            'framework' => collect($payload)->except('tree')->all(),
        ]);
    }

    public function storeResult(Request $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('update', $resultsFramework);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:results,id'],
            'level' => ['required', 'in:impact,outcome,output,process,activity'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'assumptions' => ['sometimes', 'array'],
            'risks' => ['sometimes', 'array'],
        ]);

        $result = $this->resultsFrameworkService->addResult($resultsFramework, $data);

        return ApiResponse::success($result, 'Result added.', status: 201);
    }

    public function linkIndicator(Request $request, Result $result): JsonResponse
    {
        $result->loadMissing('framework');
        $this->authorize('update', $result->framework);

        $data = $request->validate([
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
        ]);

        $indicator = $this->resultsFrameworkService->linkIndicator($result, (int) $data['indicator_id']);

        return ApiResponse::success([
            'id' => $indicator->id,
            'result_id' => $indicator->result_id,
            'results_framework_id' => $indicator->results_framework_id,
            'result_level' => $indicator->result_level instanceof \BackedEnum ? $indicator->result_level->value : $indicator->result_level,
        ], 'Indicator linked to result.');
    }

    public function submit(WorkflowCommentRequest $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('submit', $resultsFramework);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->submit($resultsFramework, $request->validated('comment'))),
            'Results framework submitted.',
        );
    }

    public function approve(WorkflowCommentRequest $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('approve', $resultsFramework);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->approve($resultsFramework, $request->validated('comment'))),
            'Results framework approved.',
        );
    }

    public function activate(WorkflowCommentRequest $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('manage', $resultsFramework);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->activate($resultsFramework, $request->validated('comment'))),
            'Results framework activated.',
        );
    }

    public function returnForCorrection(Request $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('review', $resultsFramework);

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        return ApiResponse::success(
            new ResultsFrameworkResource(
                $this->resultsFrameworkService->returnForCorrection($resultsFramework, $data['comment']),
            ),
            'Results framework returned for correction.',
        );
    }

    public function archive(WorkflowCommentRequest $request, ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('manage', $resultsFramework);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->archive($resultsFramework, $request->validated('comment'))),
            'Results framework archived.',
        );
    }

    public function version(ResultsFramework $resultsFramework): JsonResponse
    {
        $this->authorize('update', $resultsFramework);

        return ApiResponse::success(
            new ResultsFrameworkResource($this->resultsFrameworkService->createNewVersion($resultsFramework)),
            'New results framework version created.',
            status: 201,
        );
    }

    public function destroyResult(Result $result): JsonResponse
    {
        $result->loadMissing('framework');
        $this->authorize('update', $result->framework);

        $this->resultsFrameworkService->deleteResult($result);

        return ApiResponse::success(null, 'Result deleted.');
    }
}
