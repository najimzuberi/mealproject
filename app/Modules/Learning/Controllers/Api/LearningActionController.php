<?php

namespace App\Modules\Learning\Controllers\Api;

use App\Enums\LearningActionStatus;
use App\Modules\Learning\Models\LearningAction;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningActionController extends Controller
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('evaluations.view') || $request->user()?->can('lessons.view'), 403);

        $query = LearningAction::query()->where('organization_id', $this->organizationContext->id());
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function complete(Request $request, LearningAction $learningAction): JsonResponse
    {
        abort_unless($request->user()?->can('evaluations.manage') || $request->user()?->can('lessons.manage'), 403);

        if ($learningAction->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        $data = $request->validate([
            'progress_notes' => ['nullable', 'string'],
            'completed_on' => ['nullable', 'date'],
        ]);

        $learningAction->status = LearningActionStatus::Completed->value;
        $learningAction->completed_on = $data['completed_on'] ?? now()->toDateString();
        $learningAction->progress_notes = $data['progress_notes'] ?? $learningAction->progress_notes;
        $learningAction->save();

        return ApiResponse::success($learningAction->fresh(), 'Action completed.');
    }
}
