<?php

namespace App\Modules\DataCollection\Controllers\Api;

use App\Modules\DataCollection\Models\Submission;
use App\Modules\DataCollection\Resources\SubmissionResource;
use App\Modules\DataCollection\Services\SubmissionService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function __construct(
        protected SubmissionService $submissionService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);

        $query = Submission::query()
            ->with(['indicator', 'values'])
            ->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('monitoring_plan_id')) {
            $query->where('monitoring_plan_id', $planId);
        }

        if ($request->boolean('mine')) {
            $query->where('submitted_by', $request->user()?->id);
        }

        return ApiResponse::success(SubmissionResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Submission::class);

        $data = $request->validate($this->rules());

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->create($data)),
            'Submission draft created.',
            status: 201,
        );
    }

    public function show(Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        if ($submission->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        return ApiResponse::success(new SubmissionResource(
            $submission->load(['values', 'indicator', 'assignment', 'approvalHistories']),
        ));
    }

    public function update(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $data = $request->validate($this->rules(false));

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->update($submission, $data)),
            'Submission updated.',
        );
    }

    public function submit(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('submit', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->submit($submission, $request->validated('comment'))),
            'Submission submitted.',
        );
    }

    public function returnSubmission(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('verify', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->returnForCorrection($submission, $request->validated('comment'))),
            'Submission returned.',
        );
    }

    public function verify(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('verify', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->verify($submission, $request->validated('comment'))),
            'Submission verified.',
        );
    }

    public function validateSubmission(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('validate', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->validate($submission, $request->validated('comment'))),
            'Submission validated.',
        );
    }

    public function approve(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('approve', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->approve($submission, $request->validated('comment'))),
            'Submission approved.',
        );
    }

    public function publish(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('manage', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->publish($submission, $request->validated('comment'))),
            'Submission published.',
        );
    }

    public function lock(WorkflowCommentRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('manage', $submission);

        return ApiResponse::success(
            new SubmissionResource($this->submissionService->lock($submission, $request->validated('comment'))),
            'Submission locked.',
        );
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Submission::class);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'monitoring_plan_id' => ['nullable', 'integer', 'exists:monitoring_plans,id'],
        ]);

        $result = $this->submissionService->import(
            $data['file'],
            $data['monitoring_plan_id'] ?? null,
        );

        return ApiResponse::success([
            'import_job_id' => $result['job']->id,
            'status' => $result['job']->status,
            'created' => $result['created'],
            'failed_rows' => $result['job']->failed_rows,
            'errors' => $result['job']->errors,
        ], 'Import processed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $creating = true): array
    {
        return [
            'monitoring_plan_item_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:monitoring_plan_items,id'],
            'reporting_assignment_id' => ['nullable', 'integer', 'exists:reporting_assignments,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'numerator' => ['nullable', 'numeric'],
            'denominator' => ['nullable', 'numeric'],
            'value' => ['nullable', 'numeric'],
            'disaggregations' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'values' => ['sometimes', 'array'],
            'values.*.dimension' => ['nullable', 'string', 'max:100'],
            'values.*.label' => ['nullable', 'string', 'max:255'],
            'values.*.value' => ['nullable', 'numeric'],
        ];
    }
}
