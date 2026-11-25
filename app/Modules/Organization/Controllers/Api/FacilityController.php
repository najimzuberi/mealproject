<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Requests\StoreFacilityRequest;
use App\Modules\Organization\Requests\UpdateFacilityRequest;
use App\Modules\Organization\Resources\FacilityResource;
use App\Modules\Organization\Services\FacilityService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;

class FacilityController extends Controller
{
    public function __construct(
        protected FacilityService $facilityService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Facility::class);

        return ApiResponse::success(FacilityResource::collection(
            Facility::query()->where('organization_id', $this->organizationContext->id())->paginate(),
        ));
    }

    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $this->authorize('create', Facility::class);

        return ApiResponse::success(
            new FacilityResource($this->facilityService->create($request->validated())),
            status: 201,
        );
    }

    public function show(Facility $facility): JsonResponse
    {
        $this->authorize('view', $facility);

        return ApiResponse::success(new FacilityResource($facility));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $this->authorize('update', $facility);

        return ApiResponse::success(new FacilityResource($this->facilityService->update($facility, $request->validated())));
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $this->authorize('delete', $facility);

        $this->facilityService->delete($facility);

        return ApiResponse::success(null, 'Facility deleted.');
    }
}
