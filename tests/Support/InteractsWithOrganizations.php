<?php

namespace Tests\Support;

use App\Models\User;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Services\OrganizationBootstrapService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SectorSeeder;
use Laravel\Sanctum\Sanctum;

trait InteractsWithOrganizations
{
    protected function seedAccessControl(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SectorSeeder::class);
    }

    /**
     * @return array{user: User, organization: Organization, token: string}
     */
    protected function createAdminContext(array $organizationOverrides = []): array
    {
        $organization = Organization::factory()->create(array_merge([
            'status' => 'active',
        ], $organizationOverrides));

        app(OrganizationBootstrapService::class)->bootstrapRolesForOrganization($organization);

        $user = User::factory()->create(['status' => 'active']);

        $organization->users()->attach($user->id, [
            'status' => 'active',
            'is_default' => true,
            'joined_at' => now(),
        ]);

        setPermissionsTeamId($organization->id);
        $user->assignRole('system_administrator');
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'organization', 'token');
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function orgHeaders(Organization $organization, string $token, array $headers = []): array
    {
        return array_merge([
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ], $headers);
    }

    protected function actingAsOrgAdmin(User $user, Organization $organization): User
    {
        setPermissionsTeamId($organization->id);
        Sanctum::actingAs($user);

        return $user;
    }
}
