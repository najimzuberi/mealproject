<?php

namespace App\Modules\Lesson\Controllers\Api;

use App\Modules\Lesson\Models\Lesson;
use App\Modules\Lesson\Resources\LessonResource;
use App\Modules\Lesson\Services\LessonService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        protected LessonService $lessonService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lesson::class);
        $query = Lesson::query()->where('organization_id', $this->organizationContext->id());
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(LessonResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Lesson::class);
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'evaluation_id' => ['nullable', 'integer', 'exists:evaluations,id'],
            'feedback_case_id' => ['nullable', 'integer', 'exists:feedback_cases,id'],
            'context' => ['nullable', 'string'],
            'challenge' => ['nullable', 'string'],
            'what_worked' => ['nullable', 'string'],
            'what_did_not_work' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'responsible_person_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_required' => ['nullable', 'string'],
            'links' => ['sometimes', 'array'],
            'links.*.linkable_type' => ['required', 'string'],
            'links.*.linkable_id' => ['required', 'integer'],
        ]);

        return ApiResponse::success(new LessonResource($this->lessonService->create($data)), 'Lesson documented.', status: 201);
    }

    public function show(Lesson $lesson): JsonResponse
    {
        $this->authorize('view', $lesson);

        return ApiResponse::success(new LessonResource($lesson->load(['links', 'actions', 'project', 'programme', 'evaluation', 'approvalHistories'])));
    }

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'evaluation_id' => ['nullable', 'integer', 'exists:evaluations,id'],
            'feedback_case_id' => ['nullable', 'integer', 'exists:feedback_cases,id'],
            'context' => ['nullable', 'string'],
            'challenge' => ['nullable', 'string'],
            'what_worked' => ['nullable', 'string'],
            'what_did_not_work' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'responsible_person_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_required' => ['nullable', 'string'],
            'links' => ['sometimes', 'array'],
            'links.*.linkable_type' => ['required', 'string'],
            'links.*.linkable_id' => ['required', 'integer'],
        ]);

        return ApiResponse::success(new LessonResource($this->lessonService->update($lesson, $data)), 'Lesson updated.');
    }

    public function submit(WorkflowCommentRequest $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('submit', $lesson);

        return ApiResponse::success(new LessonResource($this->lessonService->submit($lesson, $request->validated('comment'))), 'Lesson submitted.');
    }

    public function approve(WorkflowCommentRequest $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('approve', $lesson);

        return ApiResponse::success(new LessonResource($this->lessonService->approve($lesson, $request->validated('comment'))), 'Lesson approved.');
    }

    public function assignAction(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('manage', $lesson);
        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'responsible_person_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'due_on' => ['nullable', 'date'],
            'required_evidence' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new LessonResource($this->lessonService->assignAction($lesson, $data)), 'Action assigned.');
    }

    public function apply(WorkflowCommentRequest $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('manage', $lesson);

        return ApiResponse::success(new LessonResource($this->lessonService->markApplied($lesson, $request->validated('comment'))), 'Lesson marked as applied.');
    }
}
