<?php

namespace App\Modules\Lesson\Services;

use App\Enums\ApprovalAction;
use App\Enums\AuditAction;
use App\Enums\LearningActionStatus;
use App\Enums\LessonStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Learning\Models\LearningAction;
use App\Modules\Lesson\Models\Lesson;
use App\Modules\Lesson\Models\LessonLink;
use App\Shared\Services\WorkflowService;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LessonService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
        protected WorkflowService $workflowService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lesson
    {
        return DB::transaction(function () use ($data) {
            $lesson = Lesson::query()->create([
                ...$this->attributes($data),
                'organization_id' => $this->organizationContext->id(),
                'reference' => $data['reference'] ?? $this->nextReference(),
                'status' => LessonStatus::Documented->value,
                'created_by' => Auth::id(),
            ]);

            $this->syncLinks($lesson, $data['links'] ?? []);
            $this->auditService->log(AuditAction::Created, 'lessons', Lesson::class, $lesson->id);

            return $this->load($lesson);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lesson $lesson, array $data): Lesson
    {
        $this->assertOrg($lesson);

        return DB::transaction(function () use ($lesson, $data) {
            $lesson->fill($this->attributes($data))->save();
            if (array_key_exists('links', $data)) {
                $this->syncLinks($lesson, $data['links'] ?? []);
            }
            $this->auditService->log(AuditAction::Updated, 'lessons', Lesson::class, $lesson->id);

            return $this->load($lesson->fresh());
        });
    }

    public function submit(Lesson $lesson, ?string $comment = null): Lesson
    {
        $this->assertOrg($lesson);

        return $this->load($this->workflowService->transition(
            $lesson,
            LessonStatus::UnderReview->value,
            ApprovalAction::Submitted,
            [LessonStatus::Identified->value, LessonStatus::Documented->value, LessonStatus::Returned->value],
            $comment,
            'lessons',
        ));
    }

    public function approve(Lesson $lesson, ?string $comment = null): Lesson
    {
        $this->assertOrg($lesson);

        return $this->load($this->workflowService->transition(
            $lesson,
            LessonStatus::Approved->value,
            ApprovalAction::Approved,
            [LessonStatus::UnderReview->value],
            $comment,
            'lessons',
            false,
            true,
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assignAction(Lesson $lesson, array $data): Lesson
    {
        $this->assertOrg($lesson);

        LearningAction::query()->create([
            'organization_id' => $lesson->organization_id,
            'actionable_type' => Lesson::class,
            'actionable_id' => $lesson->id,
            'description' => $data['description'] ?? ($lesson->action_required ?: 'Apply lesson: '.$lesson->title),
            'responsible_person_id' => $data['responsible_person_id'] ?? $lesson->responsible_person_id,
            'department_id' => $data['department_id'] ?? null,
            'due_on' => $data['due_on'] ?? null,
            'required_evidence' => $data['required_evidence'] ?? null,
            'status' => LearningActionStatus::Open->value,
            'created_by' => Auth::id(),
        ]);

        $lesson->status = LessonStatus::ActionAssigned->value;
        $lesson->save();

        $this->auditService->log(AuditAction::Updated, 'lessons', Lesson::class, $lesson->id, null, [
            'status' => LessonStatus::ActionAssigned->value,
        ]);

        return $this->load($lesson->fresh());
    }

    public function markApplied(Lesson $lesson, ?string $comment = null): Lesson
    {
        $this->assertOrg($lesson);

        $lesson->status = LessonStatus::Applied->value;
        $lesson->applied_at = now();
        $lesson->save();

        if (method_exists($lesson, 'recordApproval')) {
            $lesson->recordApproval(ApprovalAction::Activated, LessonStatus::ActionAssigned->value, LessonStatus::Applied->value, Auth::id(), $comment);
        }

        $this->auditService->log(AuditAction::Updated, 'lessons', Lesson::class, $lesson->id, null, [
            'status' => LessonStatus::Applied->value,
        ]);

        return $this->load($lesson->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return collect($data)->only([
            'title', 'description', 'source', 'project_id', 'programme_id', 'evaluation_id',
            'feedback_case_id', 'context', 'challenge', 'what_worked', 'what_did_not_work',
            'recommendation', 'responsible_person_id', 'action_required',
        ])->all();
    }

    /**
     * @param  list<array{linkable_type: string, linkable_id: int}>  $links
     */
    protected function syncLinks(Lesson $lesson, array $links): void
    {
        $lesson->links()->delete();
        foreach ($links as $link) {
            LessonLink::query()->create([
                'organization_id' => $lesson->organization_id,
                'lesson_id' => $lesson->id,
                'linkable_type' => $link['linkable_type'],
                'linkable_id' => $link['linkable_id'],
            ]);
        }
    }

    protected function nextReference(): string
    {
        $count = Lesson::query()->where('organization_id', $this->organizationContext->id())->count() + 1;

        return sprintf('LL-%04d', $count);
    }

    protected function load(Lesson $lesson): Lesson
    {
        return $lesson->load(['links', 'actions', 'project', 'programme', 'evaluation', 'approvalHistories']);
    }

    protected function assertOrg(Lesson $lesson): void
    {
        if ($lesson->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
