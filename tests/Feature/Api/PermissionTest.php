<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_field_officer_cannot_create_department(): void
    {
        $context = $this->createOrganizationContext();
        $officer = User::factory()->create(['status' => 'active']);

        $context['organization']->users()->attach($officer->id, [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $fieldRole = Role::query()
            ->where('organization_id', $context['organization']->id)
            ->where('name', 'field_officer')
            ->firstOrFail();

        setPermissionsTeamId($context['organization']->id);
        $officer->syncRoles([$fieldRole->id]);

        $token = $officer->createToken('test')->plainTextToken;
        $headers = $this->withOrganizationHeaders($token, $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/departments', [
                'name' => 'Programs',
                'code' => 'prog',
            ])
            ->assertForbidden();
    }

    public function test_six_primary_roles_are_bootstrapped_for_organization(): void
    {
        $context = $this->createOrganizationContext();

        $expected = [
            'system_administrator',
            'project_manager',
            'me_officer',
            'field_officer',
            'verification_supervisor',
            'donor_officer',
        ];

        $roles = Role::query()
            ->where('organization_id', $context['organization']->id)
            ->pluck('name')
            ->all();

        foreach ($expected as $role) {
            $this->assertContains($role, $roles);
        }
    }

    public function test_donor_officer_cannot_manage_roles(): void
    {
        $context = $this->createOrganizationContext();
        $donor = User::factory()->create(['status' => 'active']);

        $context['organization']->users()->attach($donor->id, [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $donorRole = Role::query()
            ->where('organization_id', $context['organization']->id)
            ->where('name', 'donor_officer')
            ->firstOrFail();

        setPermissionsTeamId($context['organization']->id);
        $donor->syncRoles([$donorRole->id]);

        $token = $donor->createToken('test')->plainTextToken;

        $this->withHeaders($this->withOrganizationHeaders($token, $context['organization']))
            ->postJson('/api/v1/roles', [
                'name' => 'custom_role',
                'display_name' => 'Custom',
            ])
            ->assertForbidden();
    }

    public function test_system_administrator_can_assign_roles_to_user(): void
    {
        $context = $this->createOrganizationContext();
        $member = User::factory()->create(['status' => 'active']);
        $context['organization']->users()->attach($member->id, [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $role = Role::query()
            ->where('organization_id', $context['organization']->id)
            ->where('name', 'field_officer')
            ->firstOrFail();

        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->putJson("/api/v1/users/{$member->id}/roles", [
                'role_ids' => [$role->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
