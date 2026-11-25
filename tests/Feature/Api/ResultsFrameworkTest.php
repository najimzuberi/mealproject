<?php

namespace Tests\Feature\Api;

use App\Enums\ResultLevel;
use App\Enums\WorkflowStatus;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ResultsFrameworkTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_and_list_results_framework(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/v1/results-frameworks', [
                'project_id' => $project->id,
                'title' => 'Project RF',
                'theory_of_change' => 'If we train, outcomes improve.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Project RF');

        $this->withHeaders($headers)
            ->getJson('/api/v1/results-frameworks')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.title', 'Project RF');
    }

    public function test_cannot_update_framework_when_not_draft(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $framework = ResultsFramework::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => WorkflowStatus::Approved,
        ]);

        $this->withHeaders($headers)
            ->putJson("/api/v1/results-frameworks/{$framework->id}", [
                'title' => 'Updated title',
            ])
            ->assertStatus(422);
    }

    public function test_can_build_hierarchy_and_link_indicator(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $frameworkResponse = $this->withHeaders($headers)
            ->postJson('/api/v1/results-frameworks', [
                'project_id' => $project->id,
                'title' => 'Hierarchy RF',
            ])
            ->assertCreated();

        $frameworkId = $frameworkResponse->json('data.id');

        $impact = $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$frameworkId}/results", [
                'level' => ResultLevel::Impact->value,
                'title' => 'Impact result',
            ])
            ->assertCreated()
            ->json('data');

        $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$frameworkId}/results", [
                'parent_id' => $impact['id'],
                'level' => ResultLevel::Outcome->value,
                'title' => 'Outcome child',
            ])
            ->assertCreated();

        $hierarchyResponse = $this->withHeaders($headers)
            ->getJson("/api/v1/results-frameworks/{$frameworkId}/hierarchy")
            ->assertOk();

        $hierarchyResponse
            ->assertJsonPath('data.0.title', 'Impact result')
            ->assertJsonPath('data.0.children.0.title', 'Outcome child');

        $indicator = Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/results/{$impact['id']}/link-indicator", [
                'indicator_id' => $indicator->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.result_id', $impact['id']);
    }

    public function test_me_officer_can_approve_framework_workflow(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $framework = ResultsFramework::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => WorkflowStatus::Draft,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$framework->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', WorkflowStatus::UnderReview->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$framework->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', WorkflowStatus::Approved->value);
    }

    public function test_can_return_and_version_results_framework(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $frameworkId = $this->withHeaders($headers)
            ->postJson('/api/v1/results-frameworks', [
                'project_id' => $project->id,
                'title' => 'RF Returnable',
                'theory_of_change' => 'If then',
                'results' => [
                    [
                        'level' => 'impact',
                        'title' => 'Impact',
                        'children' => [
                            ['level' => 'outcome', 'title' => 'Outcome'],
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/results-frameworks/{$frameworkId}/submit")->assertOk();
        $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$frameworkId}/return", ['comment' => 'Need clearer TOC'])
            ->assertOk()
            ->assertJsonPath('data.status', WorkflowStatus::Returned->value);

        $this->withHeaders($headers)->postJson("/api/v1/results-frameworks/{$frameworkId}/submit")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/results-frameworks/{$frameworkId}/approve")->assertOk();

        $versionId = $this->withHeaders($headers)
            ->postJson("/api/v1/results-frameworks/{$frameworkId}/version")
            ->assertCreated()
            ->assertJsonPath('data.status', WorkflowStatus::Draft->value)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.parent_framework_id', $frameworkId)
            ->json('data.id');

        $this->assertNotEquals($frameworkId, $versionId);
    }
}
