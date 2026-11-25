<?php

namespace Tests\Feature\Api;

use App\Enums\MonitoringPlanStatus;
use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class SubmissionWorkflowTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_activate_monitoring_plan_generates_assignments(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create(['organization_id' => $context['organization']->id]);
        $indicator = Indicator::factory()->approved()->create(['organization_id' => $context['organization']->id]);

        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
            'status' => MonitoringPlanStatus::Approved,
        ]);

        MonitoringPlanItem::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'indicator_id' => $indicator->id,
            'responsible_collector_id' => $context['user']->id,
            'supervisor_id' => $context['user']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/monitoring-plans/{$plan->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', MonitoringPlanStatus::Active->value);

        $this->assertDatabaseHas('reporting_assignments', [
            'monitoring_plan_id' => $plan->id,
            'assigned_to' => $context['user']->id,
            'status' => 'pending',
        ]);
    }

    public function test_submission_requires_active_monitoring_plan(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create(['organization_id' => $context['organization']->id]);
        $indicator = Indicator::factory()->approved()->create(['organization_id' => $context['organization']->id]);
        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
            'status' => MonitoringPlanStatus::Draft,
        ]);
        $item = MonitoringPlanItem::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'indicator_id' => $indicator->id,
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/v1/submissions', [
                'monitoring_plan_item_id' => $item->id,
                'value' => 42,
            ])
            ->assertStatus(422);
    }

    public function test_full_submission_approval_workflow(): void
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
            'responsible_collector_id' => $context['user']->id,
        ]);

        $create = $this->withHeaders($headers)
            ->postJson('/api/v1/submissions', [
                'monitoring_plan_item_id' => $item->id,
                'numerator' => 40,
                'denominator' => 50,
                'values' => [
                    ['dimension' => 'sex', 'label' => 'Female', 'value' => 20],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', SubmissionStatus::Draft->value);

        $id = $create->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/submit")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Submitted->value);
        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/verify")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Verified->value);
        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/validate")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Validated->value);
        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/approve")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Approved->value);
        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/publish")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Published->value);
        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/lock")->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Locked->value);
    }

    public function test_return_requires_comment(): void
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

        $id = $this->withHeaders($headers)
            ->postJson('/api/v1/submissions', [
                'monitoring_plan_item_id' => $item->id,
                'value' => 10,
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/submissions/{$id}/submit")->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/submissions/{$id}/return", [])
            ->assertStatus(422);

        $this->withHeaders($headers)
            ->postJson("/api/v1/submissions/{$id}/return", ['comment' => 'Fix denominator'])
            ->assertOk()
            ->assertJsonPath('data.status', SubmissionStatus::Returned->value);
    }

    public function test_organization_isolation_for_assignments(): void
    {
        $orgA = $this->createOrganizationContext(roleNames: ['me_officer']);
        $orgB = $this->createOrganizationContext(roleNames: ['me_officer']);

        $assignment = ReportingAssignment::factory()->create([
            'organization_id' => $orgA['organization']->id,
            'assigned_to' => $orgA['user']->id,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($orgB['token'], $orgB['organization']))
            ->getJson("/api/v1/reporting-assignments/{$assignment->id}")
            ->assertNotFound();
    }
}
