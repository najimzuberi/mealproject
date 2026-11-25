<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Requests\StoreDepartmentRequest;
use App\Modules\Organization\Requests\UpdateDepartmentRequest;
use App\Modules\Organization\Resources\DepartmentResource;
use App\Modules\Organization\Services\DepartmentService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::query()
            ->where('organization_id', $this->organizationContext->id());

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(DepartmentResource::collection($query->paginate()));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $department = $this->departmentService->create($request->validated());

        return ApiResponse::success(new DepartmentResource($department), status: 201);
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return ApiResponse::success(new DepartmentResource($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        return ApiResponse::success(new DepartmentResource(
            $this->departmentService->update($department, $request->validated()),
        ));
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $this->departmentService->delete($department);

        return ApiResponse::success(null, 'Department deleted.');
    }
}
