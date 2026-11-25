<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\MembershipStatus;
use App\Enums\RecordStatus;
use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\AccessControl\Services\RoleService;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected AuditService $auditService,
        protected RoleService $roleService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $organizationId): User
    {
        return DB::transaction(function () use ($data, $organizationId) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => $data['status'] ?? RecordStatus::Active->value,
            ]);

            $this->assignToOrganization($user, $organizationId, $data['membership_status'] ?? MembershipStatus::Active->value);

            if (! empty($data['role_ids'])) {
                $this->assignRoles($user, (array) $data['role_ids'], $organizationId);
            }

            $this->auditService->log(
                AuditAction::Created,
                'users',
                User::class,
                $user->id,
                null,
                $user->only(['name', 'email', 'status']),
            );

            return $user->load('organizations');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, int $organizationId): User
    {
        $this->ensureOrganizationMembership($user, $organizationId);

        $old = $user->only(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'status'])));
        $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'status'])));
        $user->save();

        $this->auditService->log(
            AuditAction::Updated,
            'users',
            User::class,
            $user->id,
            $old,
            $user->only(array_keys($old)),
        );

        return $user;
    }

    public function assignToOrganization(
        User $user,
        int $organizationId,
        ?string $status = null,
    ): void {
        $status ??= MembershipStatus::Active->value;

        $user->organizations()->syncWithoutDetaching([
            $organizationId => [
                'status' => $status,
                'joined_at' => now(),
            ],
        ]);

        $this->auditService->log(
            AuditAction::Updated,
            'users',
            User::class,
            $user->id,
            null,
            ['organization_id' => $organizationId, 'membership_status' => $status],
        );
    }

    public function activate(User $user, int $organizationId): User
    {
        $this->ensureOrganizationMembership($user, $organizationId);
        $user->status = RecordStatus::Active->value;
        $user->save();

        $this->auditService->log(AuditAction::Activated, 'users', User::class, $user->id);

        return $user;
    }

    public function deactivate(User $user, int $organizationId): User
    {
        $this->ensureOrganizationMembership($user, $organizationId);
        $user->status = RecordStatus::Inactive->value;
        $user->save();

        $this->auditService->log(AuditAction::Deactivated, 'users', User::class, $user->id);

        return $user;
    }

    public function resetPassword(User $user, string $password, int $organizationId): User
    {
        $this->ensureOrganizationMembership($user, $organizationId);
        $user->password = Hash::make($password);
        $user->save();

        $this->auditService->log(
            AuditAction::Updated,
            'users',
            User::class,
            $user->id,
            null,
            ['password' => '[redacted]'],
        );

        return $user;
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function assignRoles(User $user, array $roleIds, int $organizationId): User
    {
        $this->ensureOrganizationMembership($user, $organizationId);

        $roles = Role::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $roleIds)
            ->get();

        setPermissionsTeamId($organizationId);
        $user->syncRoles($roles);

        $this->auditService->log(
            AuditAction::RoleAssigned,
            'users',
            User::class,
            $user->id,
            null,
            ['roles' => $roles->pluck('name')->all()],
        );

        return $user->load('roles');
    }

    protected function ensureOrganizationMembership(User $user, int $organizationId): void
    {
        $exists = $user->organizations()->where('organizations.id', $organizationId)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this organization.'],
            ]);
        }
    }
}
