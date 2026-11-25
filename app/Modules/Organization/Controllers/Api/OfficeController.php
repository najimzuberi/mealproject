<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Requests\StoreOfficeRequest;
use App\Modules\Organization\Requests\UpdateOfficeRequest;
use App\Modules\Organization\Resources\OfficeResource;
use App\Modules\Organization\Services\OfficeService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;

class OfficeController extends Controller
{
    public function __construct(
        protected OfficeService $officeService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Office::class);

        return ApiResponse::success(OfficeResource::collection(
            Office::query()->where('organization_id', $this->organizationContext->id())->paginate(),
        ));
    }

    public function store(StoreOfficeRequest $request): JsonResponse
    {
        $this->authorize('create', Office::class);

        return ApiResponse::success(
            new OfficeResource($this->officeService->create($request->validated())),
            status: 201,
        );
    }

    public function show(Office $office): JsonResponse
    {
        $this->authorize('view', $office);

        return ApiResponse::success(new OfficeResource($office));
    }

    public function update(UpdateOfficeRequest $request, Office $office): JsonResponse
    {
        $this->authorize('update', $office);

        return ApiResponse::success(new OfficeResource($this->officeService->update($office, $request->validated())));
    }

    public function destroy(Office $office): JsonResponse
    {
        $this->authorize('delete', $office);

        $this->officeService->delete($office);

        return ApiResponse::success(null, 'Office deleted.');
    }
}
