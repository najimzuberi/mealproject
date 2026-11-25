<?php

namespace App\Modules\Dqa\Controllers\Api;

use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Dqa\Resources\DqaAssessmentResource;
use App\Modules\Dqa\Services\DqaService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DqaAssessmentController extends Controller
{
    public function __construct(
        protected DqaService $dqaService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DqaAssessment::class);

        $query = DqaAssessment::query()
            ->with(['submission.indicator', 'issues'])
            ->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(DqaAssessmentResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DqaAssessment::class);

        $data = $request->validate([
            'submission_id' => ['required', 'integer', 'exists:submissions,id'],
            'validity_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'reliability_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'completeness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'timeliness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'precision_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'integrity_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'summary' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return ApiResponse::success(
            new DqaAssessmentResource($this->dqaService->createAssessment($data)),
            'DQA assessment created.',
            status: 201,
        );
    }

    public function show(DqaAssessment $dqaAssessment): JsonResponse
    {
        $this->authorize('view', $dqaAssessment);

        if ($dqaAssessment->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        return ApiResponse::success(new DqaAssessmentResource(
            $dqaAssessment->load(['submission.indicator', 'issues', 'assessor']),
        ));
    }

    public function complete(Request $request, DqaAssessment $dqaAssessment): JsonResponse
    {
        $this->authorize('update', $dqaAssessment);

        $data = $request->validate([
            'summary' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            new DqaAssessmentResource($this->dqaService->complete($dqaAssessment, $data['summary'] ?? null)),
            'DQA assessment completed.',
        );
    }
}
