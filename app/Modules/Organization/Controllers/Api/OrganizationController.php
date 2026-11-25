<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\OrganizationSetting;
use App\Modules\Organization\Requests\RegisterOrganizationRequest;
use App\Modules\Organization\Requests\UpdateOrganizationRequest;
use App\Modules\Organization\Requests\UpdateOrganizationSettingsRequest;
use App\Modules\Organization\Resources\OrganizationResource;
use App\Modules\Organization\Resources\OrganizationSettingResource;
use App\Modules\Organization\Services\OrganizationService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()
            ->organizations()
            ->with('sectors')
            ->paginate();

        return ApiResponse::success(OrganizationResource::collection($organizations));
    }

    public function platformIndex(Request $request): JsonResponse
    {
        $this->authorize('platformList', Organization::class);

        return ApiResponse::success(
            OrganizationResource::collection(Organization::query()->paginate()),
        );
    }

    public function store(RegisterOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->register(
            $request->validated(),
            $request->user(),
            $request->validated('sector_ids', []),
        );

        return ApiResponse::success(new OrganizationResource($organization), 'Organization registered.', status: 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::success(new OrganizationResource($organization->load(['sectors', 'settings'])));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization = $this->organizationService->update($organization, $request->validated());

        return ApiResponse::success(new OrganizationResource($organization));
    }

    public function activate(Organization $organization): JsonResponse
    {
        $this->authorize('manage', $organization);

        return ApiResponse::success(new OrganizationResource($this->organizationService->activate($organization)));
    }

    public function deactivate(Organization $organization): JsonResponse
    {
        $this->authorize('manage', $organization);

        return ApiResponse::success(new OrganizationResource($this->organizationService->deactivate($organization)));
    }

    public function uploadLogo(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $request->validate(['logo' => ['required', 'image', 'max:2048']]);

        $organization = $this->organizationService->uploadLogo($organization, $request->file('logo'));

        return ApiResponse::success(new OrganizationResource($organization));
    }

    public function syncSectors(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $data = $request->validate([
            'sector_ids' => ['required', 'array'],
            'sector_ids.*' => ['integer', 'exists:sectors,id'],
        ]);

        $organization = $this->organizationService->syncSectors($organization, $data['sector_ids']);

        return ApiResponse::success(new OrganizationResource($organization));
    }

    public function showSettings(): JsonResponse
    {
        $organization = $this->organizationContext->organization();
        $this->authorize('view', $organization);

        return ApiResponse::success(
            OrganizationSettingResource::collection($organization->settings()->get()),
        );
    }

    public function updateSettings(UpdateOrganizationSettingsRequest $request): JsonResponse
    {
        $organization = $this->organizationContext->organization();
        $this->authorize('manage', $organization);

        foreach ($request->validated('settings') as $item) {
            OrganizationSetting::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'key' => $item['key'],
                ],
                ['value' => $item['value']],
            );
        }

        return ApiResponse::success(new OrganizationResource($organization->fresh()->load('settings')));
    }
}
