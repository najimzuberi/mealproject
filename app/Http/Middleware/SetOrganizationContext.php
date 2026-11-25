<?php

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Modules\Organization\Models\Organization;
use App\Shared\Http\ApiResponse;
use App\Shared\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization-Id')
            ?? $request->input('organization_id');

        if ($organizationId === null || $organizationId === '') {
            return ApiResponse::error('Organization context is required.', null, 400);
        }

        if (! is_numeric($organizationId)) {
            return ApiResponse::error('Invalid organization identifier.', null, 400);
        }

        $organizationId = (int) $organizationId;

        $user = $request->user();
        if (! $user) {
            return ApiResponse::error('Unauthenticated.', null, 401);
        }

        $isMember = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivot('status', MembershipStatus::Active->value)
            ->exists();

        if (! $isMember && ! $this->hasPlatformOrganizationAccess($user)) {
            return ApiResponse::error('You are not a member of this organization.', null, 403);
        }

        $organization = Organization::query()->find($organizationId);
        if (! $organization) {
            return ApiResponse::error('Organization not found.', null, 404);
        }

        setPermissionsTeamId($organizationId);
        $this->organizationContext->set($organization);

        return $next($request);
    }

    protected function hasPlatformOrganizationAccess($user): bool
    {
        $teamIds = $user->organizations()
            ->wherePivot('status', MembershipStatus::Active->value)
            ->pluck('organizations.id');

        foreach ($teamIds as $teamId) {
            setPermissionsTeamId($teamId);
            if ($user->hasRole('system_administrator')) {
                return true;
            }
        }

        return false;
    }
}
