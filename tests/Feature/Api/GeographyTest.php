<?php

namespace Tests\Feature\Api;

use App\Modules\Geography\Models\GeographicUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class GeographyTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_hierarchical_geographic_units(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $parent = $this->withHeaders($headers)
            ->postJson('/api/v1/geographic-units', [
                'name' => 'Country',
                'code' => 'country',
                'type' => 'country',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson('/api/v1/geographic-units', [
                'name' => 'District',
                'code' => 'district',
                'type' => 'district',
                'parent_id' => $parent,
            ])
            ->assertCreated()
            ->assertJsonPath('data.level', 1);
    }

    public function test_cannot_attach_parent_from_another_organization(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $parentB = GeographicUnit::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'Other Root',
            'code' => 'other',
            'type' => 'region',
            'level' => 0,
            'status' => 'active',
        ]);

        $headers = $this->withOrganizationHeaders($contextA['token'], $contextA['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/geographic-units', [
                'name' => 'Invalid Child',
                'code' => 'invalid',
                'parent_id' => $parentB->id,
            ])
            ->assertStatus(422);
    }
}
