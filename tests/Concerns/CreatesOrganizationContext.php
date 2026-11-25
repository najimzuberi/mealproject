<?php

namespace Tests\Concerns;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Services\OrganizationService;
use App\Modules\Organization\Services\UserService;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

trait CreatesOrganizationContext
{
    /**
     * @param  array<string, mixed>  $organizationData
     * @param  list<string>  $roleNames
     * @return array{user: User, organization: Organization, token: string}
     */
    protected function createOrganizationContext(
        ?User $user = null,
        array $organizationData = [],
        array $roleNames = [],
    ): array {
        $user ??= User::factory()->create([
            'status' => 'active',
        ]);

        $defaults = [
            'name' => 'Test Org '.Str::upper(Str::random(4)),
            'code' => 'test-'.Str::lower(Str::random(8)),
            'type' => OrganizationType::Ngo->value,
            'status' => OrganizationStatus::Active->value,
        ];

        /** @var OrganizationService $organizationService */
        $organizationService = app(OrganizationService::class);

        $organization = $organizationService->register(
            array_merge($defaults, $organizationData),
            $user,
        );

        $token = $user->createToken('test')->plainTextToken;

        if ($roleNames !== []) {
            setPermissionsTeamId($organization->id);
            $user->syncRoles($roleNames);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        return [
            'user' => $user->fresh(),
            'organization' => $organization->fresh(),
            'token' => $token,
        ];
    }

    protected function withOrganizationHeaders(string $token, Organization $organization): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function meOfficerHeaders(Organization $organization): array
    {
        $approver = User::factory()->create(['status' => 'active']);

        /** @var UserService $userService */
        $userService = app(UserService::class);
        $userService->assignToOrganization($approver, $organization->id);

        $roleId = Role::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'me_officer')
            ->value('id');

        $userService->assignRoles($approver, [(int) $roleId], $organization->id);
        $approver->refresh();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->withOrganizationHeaders(
            $approver->createToken('test')->plainTextToken,
            $organization,
        );
    }
}
