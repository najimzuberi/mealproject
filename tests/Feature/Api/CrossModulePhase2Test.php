<?php

namespace Tests\Feature\Api;

use App\Enums\IndicatorStatus;
use App\Enums\ResultLevel;
use App\Enums\WorkflowStatus;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class CrossModulePhase2Test extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_project_to_monitoring_plan_chain(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $frameworkId = $this->withHeaders($headers)
            ->postJson('/api/v1/results-frameworks', [
                'project_id' => $project->id,
                'title' => 'Chain RF',
            ])
            ->assertCreated()
            ->json('data.id');

        $resultId = $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$frameworkId}/results", [
                'level' => ResultLevel::Output->value,
                'title' => 'Trained volunteers',
            ])
            ->assertCreated()
            ->json('data.id');

        $indicatorId = $this->withHeaders($headers)
            ->postJson('/api/v1/indicators', [
                'code' => 'IND-CHAIN',
                'name' => 'Volunteers trained',
                'project_id' => $project->id,
                'results_framework_id' => $frameworkId,
                'result_id' => $resultId,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicatorId}/submit")
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicatorId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', IndicatorStatus::Approved->value);

        $this->withHeaders($headers)
            ->postJson('/api/v1/monitoring-plans', [
                'project_id' => $project->id,
                'code' => 'MP-CHAIN',
                'title' => 'Chain monitoring plan',
                'items' => [
                    ['indicator_id' => $indicatorId, 'target_value' => '500'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.indicator_id', $indicatorId);
    }

    public function test_organization_isolation_for_results_framework(): void
    {
        $orgA = $this->createOrganizationContext(roleNames: ['me_officer']);
        $orgB = $this->createOrganizationContext(roleNames: ['me_officer']);

        $framework = ResultsFramework::factory()->create([
            'organization_id' => $orgA['organization']->id,
            'status' => WorkflowStatus::Draft,
        ]);

        $headersB = $this->withOrganizationHeaders($orgB['token'], $orgB['organization']);

        $this->withHeaders($headersB)
            ->getJson("/api/v1/results-frameworks/{$framework->id}")
            ->assertNotFound();

        $this->withHeaders($headersB)
            ->putJson("/api/v1/results-frameworks/{$framework->id}", ['title' => 'Intrusion'])
            ->assertNotFound();
    }

    public function test_organization_isolation_for_monitoring_plan(): void
    {
        $orgA = $this->createOrganizationContext(roleNames: ['me_officer']);
        $orgB = $this->createOrganizationContext(roleNames: ['me_officer']);

        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $orgA['organization']->id,
        ]);

        $headersB = $this->withOrganizationHeaders($orgB['token'], $orgB['organization']);

        $this->withHeaders($headersB)
            ->getJson("/api/v1/monitoring-plans/{$plan->id}")
            ->assertNotFound();
    }
}
