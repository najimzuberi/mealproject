<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\FacilityType;
use App\Modules\Organization\Resources\FacilityTypeResource;
use App\Modules\Organization\Services\FacilityTypeService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityTypeController extends Controller
{
    public function __construct(
        protected FacilityTypeService $facilityTypeService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', FacilityType::class);

        $items = FacilityType::query()
            ->where('organization_id', $this->organizationContext->id())
            ->paginate();

        return ApiResponse::success(FacilityTypeResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', FacilityType::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new FacilityTypeResource($this->facilityTypeService->create($data)), status: 201);
    }

    public function show(FacilityType $facilityType): JsonResponse
    {
        $this->authorize('view', $facilityType);

        return ApiResponse::success(new FacilityTypeResource($facilityType));
    }

    public function update(Request $request, FacilityType $facilityType): JsonResponse
    {
        $this->authorize('update', $facilityType);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new FacilityTypeResource($this->facilityTypeService->update($facilityType, $data)));
    }

    public function destroy(FacilityType $facilityType): JsonResponse
    {
        $this->authorize('delete', $facilityType);
        $this->facilityTypeService->delete($facilityType);

        return ApiResponse::success(null, 'Facility type deleted.');
    }
}
