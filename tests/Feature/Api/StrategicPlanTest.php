<?php

namespace Tests\Feature\Api;

use App\Enums\StrategicPlanStatus;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class StrategicPlanTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_strategic_plan_with_nested_structure(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $response = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-2026',
                'title' => 'Corporate Strategy 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
                'themes' => [
                    ['name' => 'Growth', 'sort_order' => 0],
                ],
                'goals' => [
                    [
                        'title' => 'Expand outreach',
                        'theme_index' => 0,
                        'objectives' => [
                            [
                                'title' => 'Increase beneficiaries',
                                'kra' => [
                                    ['title' => 'Reach 10k households'],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', StrategicPlanStatus::Draft->value)
            ->assertJsonPath('data.goals.0.objectives.0.kra.0.title', 'Reach 10k households');

        $this->assertDatabaseHas('strategic_plans', [
            'id' => $response->json('data.id'),
            'code' => 'SP-2026',
            'status' => StrategicPlanStatus::Draft->value,
        ]);
    }

    public function test_can_submit_and_approve_with_me_officer(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-APPROVE',
                'title' => 'Approvals Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::UnderReview->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/approve", ['comment' => 'Looks good'])
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Approved->value);
    }

    public function test_updating_approved_plan_creates_new_draft_version(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-VERSION',
                'title' => 'Versioned Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/submit")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/approve")->assertOk();

        $response = $this->withHeaders($headers)
            ->putJson("/api/v1/strategic-plans/{$planId}", [
                'title' => 'Versioned Plan v2',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Draft->value)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.parent_plan_id', $planId)
            ->assertJsonPath('data.title', 'Versioned Plan v2');

        $this->assertDatabaseHas('strategic_plans', [
            'id' => $planId,
            'status' => StrategicPlanStatus::Approved->value,
        ]);

        $this->assertNotEquals($planId, $response->json('data.id'));
    }

    public function test_org_a_cannot_see_org_b_strategic_plan(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $planB = StrategicPlan::factory()->create([
            'organization_id' => $contextB['organization']->id,
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/strategic-plans/{$planB->id}")
            ->assertNotFound();
    }

    public function test_rejects_duplicate_plan_code_within_organization(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $payload = [
            'code' => 'SP-DUP',
            'title' => 'First Plan',
            'start_date' => '2026-01-01',
            'end_date' => '2030-12-31',
        ];

        $this->withHeaders($headers)->postJson('/api/v1/strategic-plans', $payload)->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_return_plan_for_correction_and_resubmit(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-RETURN',
                'title' => 'Returnable Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/submit")->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/return", ['comment' => 'Needs clearer mission'])
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Returned->value);

        $this->withHeaders($headers)
            ->putJson("/api/v1/strategic-plans/{$planId}", [
                'mission' => 'Updated mission after return',
            ])
            ->assertOk()
            ->assertJsonPath('data.mission', 'Updated mission after return');

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::UnderReview->value);
    }

    public function test_return_requires_comment(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-NO-COMMENT',
                'title' => 'No Comment Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/submit")->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/return", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_can_activate_archive_duplicate_and_list_versions(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-ACTIVATE',
                'title' => 'Activation Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
                'priorities' => [
                    ['title' => 'Priority One', 'code' => 'P1'],
                ],
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/submit")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/approve")->assertOk();
        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Active->value);

        $duplicateId = $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.status', StrategicPlanStatus::Draft->value)
            ->json('data.id');

        $this->assertNotEquals($planId, $duplicateId);

        $this->withHeaders($headers)
            ->getJson("/api/v1/strategic-plans/{$planId}/versions")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeaders($headers)
            ->getJson("/api/v1/strategic-plans/{$planId}/hierarchy")
            ->assertOk()
            ->assertJsonPath('data.id', $planId);

        $this->withHeaders($headers)
            ->postJson("/api/v1/strategic-plans/{$planId}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', StrategicPlanStatus::Archived->value);
    }

    public function test_can_upload_supporting_document(): void
    {
        Storage::fake('public');

        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-DOC',
                'title' => 'Document Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $file = UploadedFile::fake()->create('strategy.pdf', 120, 'application/pdf');

        $this->withHeaders($headers)
            ->post("/api/v1/strategic-plans/{$planId}/documents", [
                'file' => $file,
                'title' => 'Strategy Annex',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Strategy Annex');

        $this->assertDatabaseHas('documents', [
            'documentable_id' => $planId,
            'title' => 'Strategy Annex',
        ]);
    }

    public function test_cannot_edit_under_review_plan_silently(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer', 'project_manager']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $planId = $this->withHeaders($headers)
            ->postJson('/api/v1/strategic-plans', [
                'code' => 'SP-LOCKED',
                'title' => 'Locked Plan',
                'start_date' => '2026-01-01',
                'end_date' => '2030-12-31',
            ])
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/strategic-plans/{$planId}/submit")->assertOk();

        $this->withHeaders($headers)
            ->putJson("/api/v1/strategic-plans/{$planId}", ['title' => 'Should Fail'])
            ->assertStatus(422);
    }
}
