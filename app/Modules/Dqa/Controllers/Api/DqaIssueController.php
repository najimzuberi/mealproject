<?php

namespace App\Modules\Dqa\Controllers\Api;

use App\Modules\Dqa\Models\DqaIssue;
use App\Modules\Dqa\Resources\DqaIssueResource;
use App\Modules\Dqa\Services\DqaService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DqaIssueController extends Controller
{
    public function __construct(
        protected DqaService $dqaService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DqaIssue::class);

        $query = DqaIssue::query()
            ->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($assessmentId = $request->integer('dqa_assessment_id')) {
            $query->where('dqa_assessment_id', $assessmentId);
        }

        return ApiResponse::success(DqaIssueResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DqaIssue::class);

        $data = $request->validate([
            'dqa_assessment_id' => ['required', 'integer', 'exists:dqa_assessments,id'],
            'submission_id' => ['nullable', 'integer', 'exists:submissions,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'dimension' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'in:low,medium,high'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            new DqaIssueResource($this->dqaService->createIssue($data)),
            'DQA issue created.',
            status: 201,
        );
    }

    public function show(DqaIssue $dqaIssue): JsonResponse
    {
        $this->authorize('view', $dqaIssue);

        if ($dqaIssue->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        return ApiResponse::success(new DqaIssueResource($dqaIssue->load(['assessment', 'assignee'])));
    }

    public function resolve(Request $request, DqaIssue $dqaIssue): JsonResponse
    {
        $this->authorize('update', $dqaIssue);

        $data = $request->validate([
            'resolution_notes' => ['required', 'string'],
        ]);

        return ApiResponse::success(
            new DqaIssueResource($this->dqaService->resolveIssue($dqaIssue, $data['resolution_notes'])),
            'DQA issue resolved.',
        );
    }
}
