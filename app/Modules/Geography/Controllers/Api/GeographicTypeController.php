<?php

namespace App\Modules\Geography\Controllers\Api;

use App\Modules\Geography\Models\GeographicType;
use App\Modules\Geography\Resources\GeographicTypeResource;
use App\Modules\Geography\Services\GeographicTypeService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeographicTypeController extends Controller
{
    public function __construct(
        protected GeographicTypeService $geographicTypeService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', GeographicType::class);

        $items = GeographicType::query()
            ->where('organization_id', $this->organizationContext->id())
            ->paginate();

        return ApiResponse::success(GeographicTypeResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GeographicType::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'default_level' => ['nullable', 'integer', 'min:0', 'max:20'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new GeographicTypeResource($this->geographicTypeService->create($data)), status: 201);
    }

    public function show(GeographicType $geographicType): JsonResponse
    {
        $this->authorize('view', $geographicType);

        return ApiResponse::success(new GeographicTypeResource($geographicType));
    }

    public function update(Request $request, GeographicType $geographicType): JsonResponse
    {
        $this->authorize('update', $geographicType);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'default_level' => ['nullable', 'integer', 'min:0', 'max:20'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new GeographicTypeResource($this->geographicTypeService->update($geographicType, $data)));
    }

    public function destroy(GeographicType $geographicType): JsonResponse
    {
        $this->authorize('delete', $geographicType);
        $this->geographicTypeService->delete($geographicType);

        return ApiResponse::success(null, 'Geographic type deleted.');
    }
}
