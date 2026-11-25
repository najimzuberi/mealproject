<?php

namespace App\Modules\Audit\Controllers\Api;

use App\Modules\Audit\Models\LoginLog;
use App\Modules\Audit\Resources\LoginLogResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;

class LoginLogController extends Controller
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', LoginLog::class);

        return ApiResponse::success(LoginLogResource::collection(
            LoginLog::query()
                ->where('organization_id', $this->organizationContext->id())
                ->latest('created_at')
                ->paginate(),
        ));
    }
}
