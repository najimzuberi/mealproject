<?php

namespace Tests\Feature\Api;

use App\Enums\IndicatorStatus;
use App\Enums\MonitoringPlanStatus;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class MonitoringPlanTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_monitoring_plan_with_approved_indicator_item(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $indicator = Indicator::factory()->approved()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/v1/monitoring-plans', [
                'project_id' => $project->id,
                'code' => 'MP-001',
                'title' => 'Annual M&E Plan',
                'items' => [
                    [
                        'indicator_id' => $indicator->id,
                        'baseline_value' => '10',
                        'target_value' => '25',
                        'collection_method' => 'Survey',
                        'collection_frequency' => 'Quarterly',
                        'means_of_verification' => 'Survey reports',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'MP-001')
            ->assertJsonCount(1, 'data.items');
    }

    public function test_rejects_monitoring_plan_item_with_draft_indicator(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $indicator = Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => IndicatorStatus::Draft,
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/v1/monitoring-plans', [
                'project_id' => $project->id,
                'code' => 'MP-002',
                'title' => 'Invalid plan',
                'items' => [
                    ['indicator_id' => $indicator->id],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_return_requires_comment(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => MonitoringPlanStatus::UnderReview,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/monitoring-plans/{$plan->id}/return", [])
            ->assertStatus(422);

        $this->withHeaders($headers)
            ->postJson("/api/v1/monitoring-plans/{$plan->id}/return", [
                'comment' => 'Revise indicator mapping',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', MonitoringPlanStatus::Returned->value);
    }

    public function test_updating_active_plan_creates_new_version_and_supersedes_old(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'code' => 'MP-V',
            'status' => MonitoringPlanStatus::Active,
            'version' => 1,
        ]);

        $response = $this->withHeaders($headers)
            ->putJson("/api/v1/monitoring-plans/{$plan->id}", [
                'title' => 'Version 2 title',
            ])
            ->assertOk();

        $this->assertSame(2, $response->json('data.version'));
        $this->assertSame(MonitoringPlanStatus::Draft->value, $response->json('data.status'));

        $plan->refresh();
        $this->assertSame(MonitoringPlanStatus::Superseded->value, $plan->status->value);
    }
}
