<?php

namespace App\Modules\Auth\Controllers\Api;

use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $this->auditService->logLogin($user, 'login_failed', $credentials['organization_id'] ?? null, $request);
            }

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            return ApiResponse::error('This account is not active.', null, 403);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api-token')->plainTextToken;
        $user->forceFill(['last_login_at' => now()])->save();

        $organizationId = $credentials['organization_id'] ?? $user->organizations()
            ->wherePivot('is_default', true)
            ->value('organizations.id');

        $this->auditService->logLogin($user, 'login_success', $organizationId, $request);

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'ulid' => $user->ulid,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'default_organization_id' => $organizationId,
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $request->user()->currentAccessToken()?->delete();
            $this->auditService->logLogin($user, 'logout', null, $request);
        }

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('organizations');

        $requestedOrganizationId = $request->header('X-Organization-Id');
        $organizationId = null;

        if (is_numeric($requestedOrganizationId)) {
            $candidateId = (int) $requestedOrganizationId;
            $isMember = $user->organizations()->where('organizations.id', $candidateId)->exists();
            if ($isMember) {
                $organizationId = $candidateId;
            }
        }

        $organizationId ??= app(\App\Shared\Support\OrganizationContext::class)->id()
            ?? $user->organizations()->wherePivot('is_default', true)->value('organizations.id')
            ?? $user->organizations()->value('organizations.id');

        $roles = [];
        $permissions = [];

        if ($organizationId) {
            setPermissionsTeamId((int) $organizationId);
            $roles = $user->getRoleNames()->values()->all();
            $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        }

        return ApiResponse::success([
            'id' => $user->id,
            'ulid' => $user->ulid,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'organizations' => $user->organizations->map(fn ($org) => [
                'id' => $org->id,
                'ulid' => $org->ulid,
                'name' => $org->name,
                'code' => $org->code,
                'status' => $org->status,
                'is_default' => (bool) $org->pivot->is_default,
            ])->values()->all(),
            'active_organization_id' => $organizationId,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
