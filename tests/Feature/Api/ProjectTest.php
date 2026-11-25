<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Modules\Project\Models\Project;
use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_and_activate_project(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $plan = StrategicPlan::factory()->active()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $goal = StrategicGoal::query()->create([
            'organization_id' => $context['organization']->id,
            'strategic_plan_id' => $plan->id,
            'title' => 'Goal',
        ]);

        $objective = StrategicObjective::query()->create([
            'organization_id' => $context['organization']->id,
            'strategic_goal_id' => $goal->id,
            'title' => 'Objective',
        ]);

        $projectId = $this->withHeaders($headers)
            ->postJson('/api/v1/projects', [
                'code' => 'PRJ-100',
                'name' => 'Water Project',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'strategic_plan_id' => $plan->id,
                'strategic_objective_id' => $objective->id,
                'assumptions' => [
                    ['description' => 'Community buy-in'],
                ],
                'risks' => [
                    ['description' => 'Rain delays', 'likelihood' => 'medium', 'impact' => 'high'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', ProjectStatus::Draft->value)
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/projects/{$projectId}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Active->value);
    }

    public function test_can_sync_project_team(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $projectId = $this->withHeaders($headers)
            ->postJson('/api/v1/projects', [
                'code' => 'PRJ-TEAM',
                'name' => 'Team Project',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
            ])
            ->json('data.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/projects/{$projectId}/team", [
                'members' => [
                    ['user_id' => $context['user']->id, 'role_label' => 'Lead'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.team_members.0.role_label', 'Lead');
    }

    public function test_org_a_cannot_see_org_b_project(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $projectB = Project::factory()->create([
            'organization_id' => $contextB['organization']->id,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/projects/{$projectB->id}")
            ->assertNotFound();
    }

    public function test_can_submit_return_and_approve_project(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $projectId = $this->withHeaders($headers)
            ->postJson('/api/v1/projects', [
                'code' => 'PRJ-WF',
                'name' => 'Workflow Project',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'background' => 'Context',
                'problem_statement' => 'Need',
                'currency' => 'USD',
                'budget' => 50000,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/projects/{$projectId}/submit")->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::UnderReview->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/projects/{$projectId}/return", ['comment' => 'Clarify budget'])
            ->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Returned->value);

        $this->withHeaders($headers)->postJson("/api/v1/projects/{$projectId}/submit")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/projects/{$projectId}/approve")->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Approved->value);
        $this->withHeaders($headers)->postJson("/api/v1/projects/{$projectId}/activate")->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Active->value);
    }
}
