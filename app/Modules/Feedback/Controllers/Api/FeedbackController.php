<?php

namespace App\Modules\Feedback\Controllers\Api;

use App\Enums\ConfidentialityLevel;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Feedback\Models\FeedbackCategory;
use App\Modules\Feedback\Resources\FeedbackCaseResource;
use App\Modules\Feedback\Services\FeedbackService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function categories(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeedbackCase::class);
        $this->feedbackService->ensureDefaultCategories();

        $items = FeedbackCategory::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ApiResponse::success($items);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->authorize('create', FeedbackCase::class);
        abort_unless($request->user()?->can('feedback.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->feedbackService->createCategory($data), 'Category created.', status: 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeedbackCase::class);

        $query = FeedbackCase::query()
            ->with('category')
            ->where('organization_id', $this->organizationContext->id());

        // Sensitive/safeguarding cases excluded from general lists unless authorized.
        if (! $request->user()?->can('feedback.sensitive') && ! $request->user()?->can('feedback.manage')) {
            $query->whereNotIn('confidentiality_level', [
                ConfidentialityLevel::Restricted->value,
                ConfidentialityLevel::Safeguarding->value,
            ])->where(function ($q) {
                $q->whereDoesntHave('category', fn ($cq) => $cq->where('is_sensitive', true))
                    ->orWhereNull('feedback_category_id');
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(FeedbackCaseResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', FeedbackCase::class);
        $data = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:50'],
            'feedback_type' => ['nullable', 'in:feedback,complaint'],
            'feedback_category_id' => ['nullable', 'integer', 'exists:feedback_categories,id'],
            'description' => ['required', 'string'],
            'submission_channel' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'geographic_unit_id' => ['nullable', 'integer', 'exists:geographic_units,id'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'complainant_name' => ['nullable', 'string', 'max:255'],
            'complainant_contact' => ['nullable', 'string', 'max:255'],
            'consent_given' => ['sometimes', 'boolean'],
            'confidentiality_level' => ['nullable', Rule::enum(ConfidentialityLevel::class)],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        return ApiResponse::success(new FeedbackCaseResource($this->feedbackService->createCase($data)), 'Case registered.', status: 201);
    }

    public function show(FeedbackCase $feedbackCase): JsonResponse
    {
        $this->authorize('view', $feedbackCase);

        return ApiResponse::success(new FeedbackCaseResource($feedbackCase->load(['category', 'assignee', 'project'])));
    }

    public function assign(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $this->authorize('update', $feedbackCase);
        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'responsible_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'resolution_deadline' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        return ApiResponse::success(new FeedbackCaseResource($this->feedbackService->assign($feedbackCase, $data)), 'Case assigned.');
    }

    public function escalate(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $this->authorize('update', $feedbackCase);
        $data = $request->validate(['escalation_reason' => ['required', 'string']]);

        return ApiResponse::success(new FeedbackCaseResource($this->feedbackService->escalate($feedbackCase, $data['escalation_reason'])), 'Case escalated.');
    }

    public function resolve(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $this->authorize('update', $feedbackCase);
        $data = $request->validate([
            'investigation_notes' => ['nullable', 'string'],
            'resolution_description' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'feedback_loop_status' => ['nullable', 'string', 'max:100'],
        ]);

        return ApiResponse::success(new FeedbackCaseResource($this->feedbackService->resolve($feedbackCase, $data)), 'Case resolved.');
    }

    public function close(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $this->authorize('manage', $feedbackCase);
        $data = $request->validate(['feedback_loop_status' => ['nullable', 'string', 'max:100']]);

        return ApiResponse::success(new FeedbackCaseResource($this->feedbackService->close($feedbackCase, $data['feedback_loop_status'] ?? null)), 'Case closed.');
    }
}
