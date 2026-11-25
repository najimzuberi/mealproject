<?php

namespace Tests\Feature\Api;

use App\Enums\IndicatorStatus;
use App\Enums\ResultLevel;
use App\Modules\Indicator\Models\Indicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class IndicatorTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_indicator_with_nested_rows(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/indicators', [
                'code' => 'IND-1001',
                'name' => 'Coverage rate',
                'result_level' => ResultLevel::Outcome->value,
                'source_url' => 'https://example.org/indicator',
                'source_organization' => 'IndiKit',
                'baselines' => [
                    ['value' => '35', 'baseline_date' => '2025-01-01'],
                ],
                'targets' => [
                    ['value' => '60', 'target_period' => '2026'],
                ],
                'disaggregations' => [
                    ['dimension' => 'sex', 'label' => 'Sex', 'is_required' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'IND-1001')
            ->assertJsonCount(1, 'data.baselines')
            ->assertJsonCount(1, 'data.targets')
            ->assertJsonCount(1, 'data.disaggregations');
    }

    public function test_updating_approved_indicator_creates_new_version(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $indicator = Indicator::factory()->approved()->create([
            'organization_id' => $context['organization']->id,
            'code' => 'IND-V1',
            'name' => 'Original',
            'version' => 1,
        ]);

        $response = $this->withHeaders($headers)
            ->putJson("/api/v1/indicators/{$indicator->id}", [
                'name' => 'Revised definition',
            ])
            ->assertOk();

        $this->assertSame(2, $response->json('data.version'));
        $this->assertSame(IndicatorStatus::Draft->value, $response->json('data.status'));
        $this->assertSame('Revised definition', $response->json('data.name'));

        $indicator->refresh();
        $this->assertSame(IndicatorStatus::Superseded->value, $indicator->status->value);

        $this->withHeaders($headers)
            ->getJson("/api/v1/indicators/{$indicator->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_indicators_by_query(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
            'code' => 'IND-AAA',
            'name' => 'Alpha metric',
        ]);

        Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
            'code' => 'IND-BBB',
            'name' => 'Beta metric',
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/v1/indicators?q=Alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_return_requires_comment_and_allows_resubmit(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $indicator = Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/submit")
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/return", [])
            ->assertStatus(422);

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/return", [
                'comment' => 'Clarify denominator definition',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', IndicatorStatus::Returned->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', IndicatorStatus::UnderReview->value);
    }

    public function test_me_officer_can_approve_indicator(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $indicator = Indicator::factory()->create([
            'organization_id' => $context['organization']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', IndicatorStatus::UnderReview->value);

        $this->withHeaders($headers)
            ->postJson("/api/v1/indicators/{$indicator->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', IndicatorStatus::Approved->value);
    }
}
