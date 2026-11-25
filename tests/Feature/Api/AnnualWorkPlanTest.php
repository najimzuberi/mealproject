<?php

namespace Tests\Feature\Api;

use App\Enums\StrategicPlanStatus;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\StrategicPlanning\Models\KeyResultArea;
use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class AnnualWorkPlanTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_annual_work_plan_with_priorities(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        [$plan, $objectiveId] = $this->seedActivePlanWithObjective($context['organization']->id);

        $this->withHeaders($headers)
            ->postJson('/api/v1/annual-work-plans', [
                'strategic_plan_id' => $plan->id,
                'code' => 'AWP-2026',
                'title' => 'AWP 2026',
                'planning_year' => 2026,
                'priorities' => [
                    [
                        'strategic_objective_id' => $objectiveId,
                        'title' => 'Priority 1',
                        'activities' => [
                            [
                                'title' => 'Activity 1',
                                'milestones' => [
                                    ['quarter' => 'Q1', 'title' => 'Kickoff'],
                                    ['quarter' => 'Q4', 'title' => 'Close-out'],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.priorities.0.activities.0.milestones.0.quarter', 'Q1')
            ->assertJsonPath('data.priorities.0.activities.0.milestones.1.quarter', 'Q4');
    }

    public function test_admin_can_submit_annual_work_plan(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        [$plan, $objectiveId] = $this->seedActivePlanWithObjective($context['organization']->id);

        $awpId = $this->withHeaders($headers)
            ->postJson('/api/v1/annual-work-plans', [
                'strategic_plan_id' => $plan->id,
                'code' => 'AWP-SUBMIT',
                'title' => 'AWP Submit',
                'planning_year' => 2026,
                'priorities' => [
                    [
                        'strategic_objective_id' => $objectiveId,
                        'title' => 'Priority',
                    ],
                ],
            ])
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/annual-work-plans/{$awpId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::UnderReview->value);
    }

    public function test_me_officer_can_approve_annual_work_plan(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $plan = StrategicPlan::factory()->active()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $awp = AnnualWorkPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'strategic_plan_id' => $plan->id,
            'status' => StrategicPlanStatus::UnderReview,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/annual-work-plans/{$awp->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Approved->value);
    }

    public function test_org_a_cannot_see_org_b_annual_work_plan(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $awpB = AnnualWorkPlan::factory()->create([
            'organization_id' => $contextB['organization']->id,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/annual-work-plans/{$awpB->id}")
            ->assertNotFound();
    }

    public function test_awp_return_activate_and_budget_lines(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        [$plan, $objectiveId] = $this->seedActivePlanWithObjective($context['organization']->id);

        $awpId = $this->withHeaders($headers)
            ->postJson('/api/v1/annual-work-plans', [
                'strategic_plan_id' => $plan->id,
                'code' => 'AWP-BUDGET',
                'title' => 'Budgeted AWP',
                'planning_year' => 2026,
                'currency' => 'USD',
                'priorities' => [
                    [
                        'strategic_objective_id' => $objectiveId,
                        'title' => 'Priority',
                        'activities' => [
                            ['title' => 'Delivery Activity'],
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $activityId = $this->withHeaders($headers)
            ->getJson("/api/v1/annual-work-plans/{$awpId}")
            ->json('data.priorities.0.activities.0.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/annual-work-plans/{$awpId}/budget-lines", [
                'budget_lines' => [
                    [
                        'awp_activity_id' => $activityId,
                        'amount' => '1000.50',
                        'currency' => 'USD',
                        'quarter' => 'Q1',
                        'category' => 'Operations',
                    ],
                    [
                        'awp_activity_id' => $activityId,
                        'amount' => '499.50',
                        'currency' => 'USD',
                        'quarter' => 'Q2',
                        'category' => 'Travel',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.total_budget', '1500.00');

        $this->withHeaders($headers)
            ->getJson("/api/v1/annual-work-plans/{$awpId}/budget-summary")
            ->assertOk()
            ->assertJsonPath('data.total_budget', '1500.00')
            ->assertJsonPath('data.by_quarter.Q1', '1000.50');

        $this->withHeaders($headers)->postJson("/api/v1/annual-work-plans/{$awpId}/submit")->assertOk();
        $this->withHeaders($headers)
            ->postJson("/api/v1/annual-work-plans/{$awpId}/return", ['comment' => 'Add more detail'])
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Returned->value);

        $this->withHeaders($headers)->postJson("/api/v1/annual-work-plans/{$awpId}/submit")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/annual-work-plans/{$awpId}/approve")->assertOk();
        $this->withHeaders($headers)
            ->postJson("/api/v1/annual-work-plans/{$awpId}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Active->value);
    }

    /**
     * @return array{0: StrategicPlan, 1: int}
     */
    protected function seedActivePlanWithObjective(int $organizationId): array
    {
        $plan = StrategicPlan::factory()->active()->create([
            'organization_id' => $organizationId,
        ]);

        $goal = StrategicGoal::query()->create([
            'organization_id' => $organizationId,
            'strategic_plan_id' => $plan->id,
            'title' => 'Goal',
        ]);

        $objective = StrategicObjective::query()->create([
            'organization_id' => $organizationId,
            'strategic_goal_id' => $goal->id,
            'title' => 'Objective',
        ]);

        KeyResultArea::query()->create([
            'organization_id' => $organizationId,
            'strategic_objective_id' => $objective->id,
            'title' => 'KRA',
        ]);

        return [$plan, $objective->id];
    }
}
