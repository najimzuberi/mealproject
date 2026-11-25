<?php

namespace App\Modules\Audit\Controllers\Api;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Resources\AuditLogResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()
            ->where('organization_id', $this->organizationContext->id());

        if ($request->filled('module')) {
            $query->where('module', $request->string('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        return ApiResponse::success(AuditLogResource::collection($query->latest('created_at')->paginate()));
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        return ApiResponse::success(new AuditLogResource($auditLog));
    }
}
