<?php

namespace App\Modules\Feedback\Services;

use App\Enums\AuditAction;
use App\Enums\ConfidentialityLevel;
use App\Enums\FeedbackCaseStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Feedback\Models\FeedbackCategory;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): FeedbackCategory
    {
        $category = FeedbackCategory::query()->create([
            'organization_id' => $this->organizationContext->id(),
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditService->log(AuditAction::Created, 'feedback', FeedbackCategory::class, $category->id);

        return $category;
    }

    /**
     * Seed default categories if none exist.
     */
    public function ensureDefaultCategories(): void
    {
        $defaults = [
            ['name' => 'Service Delivery', 'code' => 'service'],
            ['name' => 'Staff Conduct', 'code' => 'conduct', 'is_sensitive' => true],
            ['name' => 'Project Implementation', 'code' => 'implementation'],
            ['name' => 'Resource Distribution', 'code' => 'resources'],
            ['name' => 'Accessibility', 'code' => 'accessibility'],
            ['name' => 'Safeguarding', 'code' => 'safeguarding', 'is_sensitive' => true],
            ['name' => 'Other', 'code' => 'other'],
        ];

        foreach ($defaults as $default) {
            FeedbackCategory::query()->firstOrCreate(
                [
                    'organization_id' => $this->organizationContext->id(),
                    'name' => $default['name'],
                ],
                [
                    'code' => $default['code'],
                    'is_sensitive' => $default['is_sensitive'] ?? false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCase(array $data): FeedbackCase
    {
        return DB::transaction(function () use ($data) {
            $case = FeedbackCase::query()->create([
                'organization_id' => $this->organizationContext->id(),
                'reference_number' => $data['reference_number'] ?? $this->nextReference(),
                'submitted_at' => $data['submitted_at'] ?? now(),
                'feedback_type' => $data['feedback_type'] ?? 'feedback',
                'feedback_category_id' => $data['feedback_category_id'] ?? null,
                'description' => $data['description'],
                'submission_channel' => $data['submission_channel'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'geographic_unit_id' => $data['geographic_unit_id'] ?? null,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'complainant_name' => ($data['is_anonymous'] ?? false) ? null : ($data['complainant_name'] ?? null),
                'complainant_contact' => ($data['is_anonymous'] ?? false) ? null : ($data['complainant_contact'] ?? null),
                'consent_given' => (bool) ($data['consent_given'] ?? false),
                'confidentiality_level' => $data['confidentiality_level'] ?? ConfidentialityLevel::Internal->value,
                'priority' => $data['priority'] ?? 'medium',
                'status' => FeedbackCaseStatus::Received->value,
                'created_by' => Auth::id(),
            ]);

            $this->auditService->log(AuditAction::Created, 'feedback', FeedbackCase::class, $case->id, null, [
                'confidentiality_level' => $case->confidentiality_level,
                'is_anonymous' => $case->is_anonymous,
            ]);

            return $case->load('category');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(FeedbackCase $case, array $data): FeedbackCase
    {
        $this->assertOrg($case);

        $case->fill([
            'assigned_to' => $data['assigned_to'],
            'responsible_department_id' => $data['responsible_department_id'] ?? $case->responsible_department_id,
            'supervisor_id' => $data['supervisor_id'] ?? $case->supervisor_id,
            'resolution_deadline' => $data['resolution_deadline'] ?? $case->resolution_deadline,
            'priority' => $data['priority'] ?? $case->priority,
            'status' => FeedbackCaseStatus::Assigned->value,
        ])->save();

        $this->auditService->log(AuditAction::Updated, 'feedback', FeedbackCase::class, $case->id, null, [
            'status' => FeedbackCaseStatus::Assigned->value,
            'assigned_to' => $case->assigned_to,
        ]);

        return $case->fresh()->load(['category', 'assignee']);
    }

    public function escalate(FeedbackCase $case, string $reason): FeedbackCase
    {
        $this->assertOrg($case);

        if (blank($reason)) {
            throw ValidationException::withMessages(['escalation_reason' => ['Escalation reason is required.']]);
        }

        $case->escalation_level = ((int) $case->escalation_level) + 1;
        $case->escalation_reason = $reason;
        $case->status = FeedbackCaseStatus::Escalated->value;
        $case->save();

        $this->auditService->log(AuditAction::Updated, 'feedback', FeedbackCase::class, $case->id, null, [
            'status' => FeedbackCaseStatus::Escalated->value,
            'escalation_level' => $case->escalation_level,
        ]);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolve(FeedbackCase $case, array $data): FeedbackCase
    {
        $this->assertOrg($case);

        $case->fill([
            'investigation_notes' => $data['investigation_notes'] ?? $case->investigation_notes,
            'resolution_description' => $data['resolution_description'],
            'action_taken' => $data['action_taken'] ?? null,
            'feedback_loop_status' => $data['feedback_loop_status'] ?? 'pending',
            'status' => FeedbackCaseStatus::Resolved->value,
            'resolved_at' => now(),
        ])->save();

        $this->auditService->log(AuditAction::Updated, 'feedback', FeedbackCase::class, $case->id, null, [
            'status' => FeedbackCaseStatus::Resolved->value,
        ]);

        return $case->fresh();
    }

    public function close(FeedbackCase $case, ?string $feedbackLoopStatus = null): FeedbackCase
    {
        $this->assertOrg($case);

        $case->status = FeedbackCaseStatus::Closed->value;
        $case->closed_at = now();
        $case->closed_by = Auth::id();
        $case->feedback_loop_status = $feedbackLoopStatus ?? $case->feedback_loop_status ?? 'closed';
        $case->save();

        $this->auditService->log(AuditAction::Updated, 'feedback', FeedbackCase::class, $case->id, null, [
            'status' => FeedbackCaseStatus::Closed->value,
        ]);

        return $case->fresh();
    }

    protected function nextReference(): string
    {
        $count = FeedbackCase::query()->where('organization_id', $this->organizationContext->id())->count() + 1;

        return sprintf('FB-%04d', $count);
    }

    protected function assertOrg(FeedbackCase $case): void
    {
        if ($case->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
