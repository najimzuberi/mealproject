<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_user_can_register_and_update_organization(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->putJson('/api/v1/organizations/'.$context['organization']->id, [
                'name' => 'Updated Org Name',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Org Name');
    }

    public function test_user_can_activate_pending_organization(): void
    {
        $context = $this->createOrganizationContext(organizationData: [
            'status' => OrganizationStatus::Pending->value,
        ]);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/organizations/'.$context['organization']->id.'/activate')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', OrganizationStatus::Active->value);
    }

    public function test_authenticated_user_can_register_new_organization(): void
    {
        $context = $this->createOrganizationContext();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$context['token'],
            'Accept' => 'application/json',
        ])->postJson('/api/v1/organizations', [
            'name' => 'Second NGO',
            'code' => 'second-ngo-'.uniqid(),
            'type' => OrganizationType::Ngo->value,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Second NGO');
    }
}
