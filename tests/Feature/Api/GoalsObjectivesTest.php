<?php

namespace Tests\Feature\Api;

use App\Enums\StrategicPlanStatus;
use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class GoalsObjectivesTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_goal_and_objective_under_plan(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-GOALS',
                'title' => 'Goals Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $goalId = $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/goals", [
                'code' => 'G1',
                'title' => 'Improve Food Security',
                'priority' => 1,
                'description' => 'Strategic goal description',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Improve Food Security')
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/goals/{$goalId}/objectives", [
                'code' => 'O1',
                'title' => 'Increase household yields',
                'kra' => [
                    ['title' => 'Extension coverage', 'code' => 'KRA-1'],
                ],
                'outcomes' => [
                    [
                        'title' => 'Higher yields',
                        'expected_change' => '20% increase',
                        'indicators' => [
                            ['name' => 'Avg yield tons/ha', 'baseline_value' => 1.2, 'target_value' => 1.5],
                        ],
                    ],
                ],
                'indicators' => [
                    ['name' => 'Households reached', 'measurement_unit' => 'count'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Increase household yields')
            ->assertJsonPath('data.kra.0.title', 'Extension coverage')
            ->assertJsonPath('data.outcomes.0.title', 'Higher yields')
            ->assertJsonPath('data.indicators.0.name', 'Households reached');

        $this->withHeaders($headers)
            ->getJson("/api/v1/strategic-plans/{$planId}/hierarchy")
            ->assertOk()
            ->assertJsonPath('data.goals.0.title', 'Improve Food Security')
            ->assertJsonPath('data.goals.0.objectives.0.title', 'Increase household yields');
    }

    public function test_cannot_attach_goal_to_foreign_plan(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $planB = StrategicPlan::factory()->create([
            'organization_id' => $contextB['organization']->id,
            'status' => StrategicPlanStatus::Draft->value,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->postJson("/api/v1/strategic-plans/{$planB->id}/goals", [
                'title' => 'Should fail',
            ])
            ->assertNotFound();
    }

    public function test_goal_belongs_to_same_organization_plan(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $plan = StrategicPlan::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $goal = StrategicGoal::query()->create([
            'organization_id' => $context['organization']->id,
            'strategic_plan_id' => $plan->id,
            'title' => 'Existing Goal',
        ]);

        $this->withHeaders($headers)
            ->getJson("/api/v1/goals/{$goal->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $goal->id);
    }
}
