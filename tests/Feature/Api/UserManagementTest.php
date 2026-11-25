<?php

namespace Tests\Feature\Api;

use App\Enums\RecordStatus;
use App\Models\User;
use App\Modules\Organization\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_admin_can_create_user_in_organization(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/users', [
                'name' => 'Field User',
                'email' => 'field@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'field@example.com');
    }

    public function test_user_can_belong_to_multiple_organizations(): void
    {
        $context = $this->createOrganizationContext();
        /** @var OrganizationService $service */
        $service = app(OrganizationService::class);

        $secondOrg = $service->register([
            'name' => 'Partner Org',
            'code' => 'partner-'.uniqid(),
            'type' => 'ngo',
            'status' => 'active',
        ], $context['user']);

        $context['user']->refresh();
        $this->assertGreaterThanOrEqual(2, $context['user']->organizations()->count());
        $this->assertTrue(
            $context['user']->organizations()->where('organizations.id', $secondOrg->id)->exists(),
        );
    }

    public function test_admin_can_deactivate_user(): void
    {
        $context = $this->createOrganizationContext();
        $member = User::factory()->create(['status' => RecordStatus::Active->value]);

        $context['organization']->users()->attach($member->id, [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson("/api/v1/users/{$member->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(RecordStatus::Inactive->value, $member->fresh()->status);
    }
}
