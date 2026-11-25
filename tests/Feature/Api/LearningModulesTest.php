<?php

namespace Tests\Feature\Api;

use App\Enums\ConfidentialityLevel;
use App\Enums\EvaluationStatus;
use App\Enums\FeedbackCaseStatus;
use App\Enums\KnowledgeDocumentStatus;
use App\Enums\LessonStatus;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Feedback\Models\FeedbackCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class LearningModulesTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_evaluation_workflow_findings_and_management_response_creates_action(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $create = $this->withHeaders($headers)
            ->postJson('/api/v1/evaluations', [
                'code' => 'EV-100',
                'title' => 'Midterm Evaluation',
                'questions' => [
                    ['question' => 'Relevance?', 'criterion' => 'Relevance'],
                ],
            ])
            ->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/evaluations/{$id}/submit")->assertOk()
            ->assertJsonPath('data.status', EvaluationStatus::UnderReview->value);
        $this->withHeaders($headers)->postJson("/api/v1/evaluations/{$id}/approve")->assertOk()
            ->assertJsonPath('data.status', EvaluationStatus::InProgress->value);

        $finding = $this->withHeaders($headers)
            ->postJson("/api/v1/evaluations/{$id}/findings", [
                'title' => 'Coverage gap',
                'conclusion' => 'Under-served districts',
            ])
            ->assertCreated();

        $rec = $this->withHeaders($headers)
            ->postJson("/api/v1/evaluations/{$id}/recommendations", [
                'evaluation_finding_id' => $finding->json('data.id'),
                'title' => 'Expand outreach',
                'priority' => 'high',
            ])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/v1/evaluation-recommendations/'.$rec->json('data.id').'/respond', [
                'management_response_status' => 'accepted',
                'management_response' => 'Accepted with budget allocation',
                'due_on' => now()->addMonth()->toDateString(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('learning_actions', [
            'organization_id' => $context['organization']->id,
            'status' => 'open',
        ]);
    }

    public function test_safeguarding_cases_are_hidden_from_general_lists(): void
    {
        $manager = $this->createOrganizationContext(roleNames: ['project_manager']);
        $headers = $this->withOrganizationHeaders($manager['token'], $manager['organization']);

        FeedbackCase::factory()->safeguarding()->create([
            'organization_id' => $manager['organization']->id,
        ]);
        FeedbackCase::factory()->create([
            'organization_id' => $manager['organization']->id,
            'confidentiality_level' => ConfidentialityLevel::Internal->value,
            'description' => 'General service delay',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/feedback-cases')
            ->assertOk();

        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame(
            ConfidentialityLevel::Internal->value,
            $response->json('data.items.0.confidentiality_level'),
        );
    }

    public function test_safeguarding_case_detail_requires_sensitive_permission(): void
    {
        $manager = $this->createOrganizationContext(roleNames: ['project_manager']);
        $headers = $this->withOrganizationHeaders($manager['token'], $manager['organization']);

        $case = FeedbackCase::factory()->safeguarding()->create([
            'organization_id' => $manager['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->getJson("/api/v1/feedback-cases/{$case->id}")
            ->assertForbidden();
    }

    public function test_feedback_case_workflow(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $id = $this->withHeaders($headers)
            ->postJson('/api/v1/feedback-cases', [
                'description' => 'Late distribution at site A',
                'feedback_type' => 'complaint',
                'priority' => 'high',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/feedback-cases/{$id}/assign", [
                'assigned_to' => $context['user']->id,
                'resolution_deadline' => now()->addDays(7)->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', FeedbackCaseStatus::Assigned->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/feedback-cases/{$id}/resolve", [
                'resolution_description' => 'Supplies delivered',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', FeedbackCaseStatus::Resolved->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/feedback-cases/{$id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', FeedbackCaseStatus::Closed->value);
    }

    public function test_lesson_workflow_and_application(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $id = $this->withHeaders($headers)
            ->postJson('/api/v1/lessons', [
                'title' => 'Community engagement works',
                'what_worked' => 'Early involvement',
                'what_did_not_work' => 'Top-down planning',
                'recommendation' => 'Co-design workshops',
                'action_required' => 'Update AWP guidance',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/lessons/{$id}/submit")->assertOk()
            ->assertJsonPath('data.status', LessonStatus::UnderReview->value);
        $this->withHeaders($headers)->postJson("/api/v1/lessons/{$id}/approve")->assertOk()
            ->assertJsonPath('data.status', LessonStatus::Approved->value);
        $this->withHeaders($headers)->postJson("/api/v1/lessons/{$id}/assign-action", [
            'due_on' => now()->addWeeks(2)->toDateString(),
        ])->assertOk()->assertJsonPath('data.status', LessonStatus::ActionAssigned->value);
        $this->withHeaders($headers)->postJson("/api/v1/lessons/{$id}/apply")->assertOk()
            ->assertJsonPath('data.status', LessonStatus::Applied->value);
    }

    public function test_knowledge_document_upload_publish_and_download(): void
    {
        Storage::fake('local');
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $file = UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf');

        $id = $this->withHeaders($headers)
            ->post('/api/v1/knowledge-documents', [
                'code' => 'KD-01',
                'title' => 'MEAL Guidance',
                'category' => 'guidance',
                'file' => $file,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/knowledge-documents/{$id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', KnowledgeDocumentStatus::Published->value);

        $this->withHeaders($headers)
            ->get("/api/v1/knowledge-documents/{$id}/download")
            ->assertOk();
    }

    public function test_organization_isolation_for_evaluations(): void
    {
        $orgA = $this->createOrganizationContext(roleNames: ['me_officer']);
        $orgB = $this->createOrganizationContext(roleNames: ['me_officer']);

        $evaluation = Evaluation::factory()->create([
            'organization_id' => $orgA['organization']->id,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($orgB['token'], $orgB['organization']))
            ->getJson("/api/v1/evaluations/{$evaluation->id}")
            ->assertNotFound();
    }
}
