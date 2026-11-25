<?php

namespace App\Modules\AccessControl\Controllers\Api;

use App\Modules\AccessControl\Models\Role;
use App\Modules\AccessControl\Requests\StoreRoleRequest;
use App\Modules\AccessControl\Requests\SyncRolePermissionsRequest;
use App\Modules\AccessControl\Resources\RoleResource;
use App\Modules\AccessControl\Services\RoleService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->where('organization_id', $this->organizationContext->id())
            ->with('permissions')
            ->paginate();

        return ApiResponse::success(RoleResource::collection($roles));
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return ApiResponse::success(Permission::query()->orderBy('name')->pluck('name'));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->createForOrganization(
            $this->organizationContext->id(),
            $request->validated('name'),
            $request->validated('display_name'),
            $request->validated('permission_names', []),
        );

        return ApiResponse::success(new RoleResource($role), status: 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return ApiResponse::success(new RoleResource($role->load('permissions')));
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roleService->syncPermissions($role, $request->validated('permission_names'));

        return ApiResponse::success(new RoleResource($role));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return ApiResponse::success(null, 'Role deleted.');
    }
}
