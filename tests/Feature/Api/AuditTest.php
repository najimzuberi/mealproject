<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_creating_department_writes_audit_log(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/departments', [
                'name' => 'Monitoring',
                'code' => 'mon',
            ])
            ->assertCreated();

        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $context['organization']->id)
                ->where('module', 'departments')
                ->exists(),
        );
    }

    public function test_user_without_audit_permission_cannot_list_logs(): void
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
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();
    }
}
