<?php

namespace Tests\Feature;

use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\FacilityType;
use App\Modules\Organization\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class OrganizationStructureEnhancementTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_department_hierarchy_and_circular_parent_are_enforced(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $parent = $this->withHeaders($headers)->postJson('/api/v1/departments', [
            'name' => 'Programmes',
            'code' => 'PROG',
        ])->assertCreated()->json('data');

        $child = $this->withHeaders($headers)->postJson('/api/v1/departments', [
            'name' => 'MEAL',
            'code' => 'MEAL',
            'parent_id' => $parent['id'],
        ])->assertCreated()->json('data');

        $this->withHeaders($headers)
            ->putJson('/api/v1/departments/'.$parent['id'], [
                'parent_id' => $child['id'],
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('departments', [
            'id' => $child['id'],
            'parent_id' => $parent['id'],
        ]);
    }

    public function test_geographic_circular_parent_is_rejected(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $country = $this->withHeaders($headers)->postJson('/api/v1/geographic-units', [
            'name' => 'Tanzania',
            'code' => 'TZ',
            'type' => 'country',
        ])->assertCreated()->json('data');

        $region = $this->withHeaders($headers)->postJson('/api/v1/geographic-units', [
            'name' => 'Dar',
            'code' => 'DAR',
            'type' => 'region',
            'parent_id' => $country['id'],
        ])->assertCreated()->json('data');

        $this->withHeaders($headers)
            ->putJson('/api/v1/geographic-units/'.$country['id'], [
                'parent_id' => $region['id'],
            ])
            ->assertStatus(422);
    }

    public function test_organization_settings_can_be_read_and_team_members_synced(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->getJson('/api/v1/organization/settings')
            ->assertOk()
            ->assertJsonPath('success', true);

        $team = $this->withHeaders($headers)->postJson('/api/v1/teams', [
            'name' => 'Field Team A',
            'code' => 'FTA',
        ])->assertCreated()->json('data');

        $this->withHeaders($headers)
            ->putJson('/api/v1/teams/'.$team['id'].'/members', [
                'user_ids' => [$context['user']->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team['id'],
            'user_id' => $context['user']->id,
        ]);

        $this->assertInstanceOf(Team::class, Team::query()->find($team['id']));
    }

    public function test_facility_types_can_be_created(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/facility-types', [
                'name' => 'Health Centre',
                'code' => 'HC',
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'HC');

        $this->assertInstanceOf(FacilityType::class, FacilityType::query()->first());
        $this->assertInstanceOf(Department::class, Department::factory()->create([
            'organization_id' => $context['organization']->id,
        ]));
        $this->assertInstanceOf(GeographicUnit::class, GeographicUnit::factory()->create([
            'organization_id' => $context['organization']->id,
        ]));
    }
}
