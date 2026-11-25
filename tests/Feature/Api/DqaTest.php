<?php

namespace Tests\Feature\Api;

use App\Enums\DqaAssessmentStatus;
use App\Enums\MonitoringPlanStatus;
use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class DqaTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_dqa_assessment_and_resolve_issue(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create(['organization_id' => $context['organization']->id]);
        $indicator = Indicator::factory()->approved()->create(['organization_id' => $context['organization']->id]);
        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
            'status' => MonitoringPlanStatus::Active,
        ]);
        $item = MonitoringPlanItem::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'indicator_id' => $indicator->id,
        ]);

        $submission = Submission::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'monitoring_plan_item_id' => $item->id,
            'indicator_id' => $indicator->id,
            'status' => SubmissionStatus::Submitted,
            'numerator' => 10,
            'denominator' => 0,
            'value' => 9999999,
            'submitted_at' => now(),
        ]);

        $assessment = $this->withHeaders($headers)
            ->postJson('/api/v1/dqa-assessments', [
                'submission_id' => $submission->id,
                'validity_score' => 70,
                'reliability_score' => 75,
                'completeness_score' => 80,
                'timeliness_score' => 85,
                'precision_score' => 70,
                'integrity_score' => 90,
                'summary' => 'Initial DQA',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', DqaAssessmentStatus::InProgress->value);

        $assessmentId = $assessment->json('data.id');
        $this->assertGreaterThan(0, count($assessment->json('data.issues')));

        $issueId = $assessment->json('data.issues.0.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/dqa-issues/{$issueId}/resolve", [
                'resolution_notes' => 'Reviewed and accepted as valid outlier.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->withHeaders($headers)
            ->postJson("/api/v1/dqa-assessments/{$assessmentId}/complete", [
                'summary' => 'Completed after review',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', DqaAssessmentStatus::Completed->value);
    }
}
