<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Models\User;
use App\Modules\Organization\Requests\StoreUserRequest;
use App\Modules\Organization\Requests\UpdateUserRequest;
use App\Modules\Organization\Resources\UserResource;
use App\Modules\Organization\Services\UserService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $organizationId = $this->organizationContext->id();
        $users = User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->paginate();

        return ApiResponse::success(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->create(
            $request->validated(),
            $this->organizationContext->id(),
        );

        return ApiResponse::success(new UserResource($user), status: 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->update($user, $request->validated(), $this->organizationContext->id());

        return ApiResponse::success(new UserResource($user));
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('manage', User::class);

        return ApiResponse::success(new UserResource(
            $this->userService->activate($user, $this->organizationContext->id()),
        ));
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('manage', User::class);

        return ApiResponse::success(new UserResource(
            $this->userService->deactivate($user, $this->organizationContext->id()),
        ));
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize('manage', User::class);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->userService->resetPassword($user, $data['password'], $this->organizationContext->id());

        return ApiResponse::success(null, 'Password reset successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('manage', User::class);

        $organizationId = $this->organizationContext->id();
        $user->organizations()->detach($organizationId);

        setPermissionsTeamId($organizationId);
        $user->syncRoles([]);

        return ApiResponse::success(null, 'User removed from organization.');
    }

    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('manage', User::class);

        $data = $request->validate([
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = $this->userService->assignRoles($user, $data['role_ids'], $this->organizationContext->id());

        return ApiResponse::success(new UserResource($user));
    }
}
