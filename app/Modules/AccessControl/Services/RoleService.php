<?php

namespace App\Modules\AccessControl\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\AccessControl\Support\Permissions;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class RoleService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function createForOrganization(
        int $organizationId,
        string $name,
        ?string $displayName = null,
        array $permissionNames = [],
    ): Role {
        return DB::transaction(function () use ($organizationId, $name, $displayName, $permissionNames) {
            setPermissionsTeamId($organizationId);

            /** @var Role $role */
            $role = Role::query()->create([
                'name' => $name,
                'display_name' => $displayName ?? $name,
                'guard_name' => 'web',
                'organization_id' => $organizationId,
                'is_system' => false,
            ]);

            if ($permissionNames !== []) {
                $role->syncPermissions($permissionNames);
            }

            $this->auditService->log(
                AuditAction::Created,
                'access_control',
                Role::class,
                $role->id,
                null,
                ['name' => $role->name, 'permissions' => $permissionNames],
            );

            return $role->load('permissions');
        });
    }

    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => ['System roles cannot be modified.'],
            ]);
        }

        $old = $role->permissions->pluck('name')->all();
        setPermissionsTeamId($role->organization_id);
        $role->syncPermissions($permissionNames);

        $this->auditService->log(
            AuditAction::PermissionChanged,
            'access_control',
            Role::class,
            $role->id,
            ['permissions' => $old],
            ['permissions' => $permissionNames],
        );

        return $role->load('permissions');
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => ['System roles cannot be deleted.'],
            ]);
        }

        $this->auditService->log(
            AuditAction::Deleted,
            'access_control',
            Role::class,
            $role->id,
            ['name' => $role->name],
            null,
        );

        $role->delete();
    }

    public function assignRoleToUser(User $user, Role $role, int $organizationId): void
    {
        setPermissionsTeamId($organizationId);
        $user->assignRole($role);

        $this->auditService->log(
            AuditAction::RoleAssigned,
            'access_control',
            User::class,
            $user->id,
            null,
            ['role' => $role->name],
            $user,
        );
    }

    public function bootstrapOrganizationRoles(int $organizationId): Collection
    {
        setPermissionsTeamId($organizationId);

        $permissions = Permission::query()
            ->whereIn('name', Permissions::ALL)
            ->pluck('name')
            ->all();

        /** @var Role $administrator */
        $administrator = Role::query()->firstOrCreate(
            [
                'name' => Permissions::ADMINISTRATOR_ROLE,
                'guard_name' => 'web',
                'organization_id' => $organizationId,
            ],
            [
                'display_name' => 'Administrator',
                'is_system' => true,
            ],
        );
        $administrator->syncPermissions($permissions);

        /** @var Role $systemAdministrator */
        $systemAdministrator = Role::query()->firstOrCreate(
            [
                'name' => Permissions::SYSTEM_ADMINISTRATOR_ROLE,
                'guard_name' => 'web',
                'organization_id' => $organizationId,
            ],
            [
                'display_name' => 'System Administrator',
                'is_system' => true,
            ],
        );
        $systemAdministrator->syncPermissions($permissions);

        return collect([$administrator, $systemAdministrator]);
    }
}
