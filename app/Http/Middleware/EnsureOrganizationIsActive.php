<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Shared\Http\ApiResponse;
use App\Shared\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsActive
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->organizationContext->organization();

        if (! $organization) {
            return ApiResponse::error('Organization context is required.', null, 400);
        }

        if ($organization->status !== OrganizationStatus::Active->value) {
            return ApiResponse::error('This organization is not active.', null, 403);
        }

        return $next($request);
    }
}
