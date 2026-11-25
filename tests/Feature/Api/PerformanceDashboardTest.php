<?php

namespace Tests\Feature\Api;

use App\Enums\MonitoringPlanStatus;
use App\Enums\ProjectStatus;
use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class PerformanceDashboardTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_dashboard_returns_summary_from_published_data_only(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => ProjectStatus::Active,
        ]);
        $indicator = Indicator::factory()->approved()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
        ]);
        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
            'status' => MonitoringPlanStatus::Active,
        ]);
        $item = MonitoringPlanItem::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'indicator_id' => $indicator->id,
            'baseline_value' => '10',
            'target_value' => '100',
        ]);

        Submission::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'monitoring_plan_item_id' => $item->id,
            'indicator_id' => $indicator->id,
            'status' => SubmissionStatus::Draft,
            'value' => 999,
        ]);

        Submission::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'monitoring_plan_item_id' => $item->id,
            'indicator_id' => $indicator->id,
            'status' => SubmissionStatus::Published,
            'value' => 80,
            'published_at' => now(),
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/performance/dashboard')
            ->assertOk();

        $this->assertSame(1, $response->json('data.summary.total_projects'));
        $this->assertSame(1, $response->json('data.summary.active_projects'));
        $this->assertNotEmpty($response->json('data.indicator_performance'));
        $this->assertEquals(80, (float) $response->json('data.indicator_performance.0.actual'));
    }

    public function test_organization_isolation_on_performance_dashboard(): void
    {
        $orgA = $this->createOrganizationContext(roleNames: ['me_officer']);
        $orgB = $this->createOrganizationContext(roleNames: ['me_officer']);

        Project::factory()->create([
            'organization_id' => $orgA['organization']->id,
            'status' => ProjectStatus::Active,
        ]);

        $response = $this->withHeaders($this->withOrganizationHeaders($orgB['token'], $orgB['organization']))
            ->getJson('/api/v1/performance/dashboard')
            ->assertOk();

        $this->assertSame(0, $response->json('data.summary.total_projects'));
    }
}
