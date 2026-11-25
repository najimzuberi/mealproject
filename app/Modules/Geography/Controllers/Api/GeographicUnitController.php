<?php

namespace App\Modules\Geography\Controllers\Api;

use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Geography\Requests\StoreGeographicUnitRequest;
use App\Modules\Geography\Requests\UpdateGeographicUnitRequest;
use App\Modules\Geography\Resources\GeographicUnitResource;
use App\Modules\Geography\Services\GeographicUnitService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;

class GeographicUnitController extends Controller
{
    public function __construct(
        protected GeographicUnitService $geographicUnitService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', GeographicUnit::class);

        return ApiResponse::success(GeographicUnitResource::collection(
            GeographicUnit::query()->where('organization_id', $this->organizationContext->id())->paginate(),
        ));
    }

    public function store(StoreGeographicUnitRequest $request): JsonResponse
    {
        $this->authorize('create', GeographicUnit::class);

        return ApiResponse::success(
            new GeographicUnitResource($this->geographicUnitService->create($request->validated())),
            status: 201,
        );
    }

    public function show(GeographicUnit $geographicUnit): JsonResponse
    {
        $this->authorize('view', $geographicUnit);

        return ApiResponse::success(new GeographicUnitResource($geographicUnit->load('children')));
    }

    public function update(UpdateGeographicUnitRequest $request, GeographicUnit $geographicUnit): JsonResponse
    {
        $this->authorize('update', $geographicUnit);

        return ApiResponse::success(new GeographicUnitResource(
            $this->geographicUnitService->update($geographicUnit, $request->validated()),
        ));
    }

    public function destroy(GeographicUnit $geographicUnit): JsonResponse
    {
        $this->authorize('delete', $geographicUnit);

        $this->geographicUnitService->delete($geographicUnit);

        return ApiResponse::success(null, 'Geographic unit deleted.');
    }
}
