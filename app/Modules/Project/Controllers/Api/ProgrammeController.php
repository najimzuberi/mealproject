<?php

namespace App\Modules\Project\Controllers\Api;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Requests\StoreProgrammeDocumentRequest;
use App\Modules\Project\Requests\StoreProgrammeRequest;
use App\Modules\Project\Requests\UpdateProgrammeRequest;
use App\Modules\Project\Resources\ProgrammeResource;
use App\Modules\Project\Services\ProgrammeService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function __construct(
        protected ProgrammeService $programmeService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Programme::class);

        $programmes = Programme::query()
            ->where('organization_id', $this->organizationContext->id())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->withCount('projects')
            ->latest('updated_at')
            ->paginate();

        return ApiResponse::success(ProgrammeResource::collection($programmes));
    }

    public function store(StoreProgrammeRequest $request): JsonResponse
    {
        $this->authorize('create', Programme::class);

        $programme = $this->programmeService->create($request->validated());

        return ApiResponse::success(new ProgrammeResource($programme), status: 201);
    }

    public function show(Programme $programme): JsonResponse
    {
        $this->authorize('view', $programme);

        return ApiResponse::success(new ProgrammeResource($this->programmeService->load($programme)));
    }

    public function update(UpdateProgrammeRequest $request, Programme $programme): JsonResponse
    {
        $this->authorize('update', $programme);

        return ApiResponse::success(new ProgrammeResource(
            $this->programmeService->update($programme, $request->validated()),
        ));
    }

    public function destroy(Programme $programme): JsonResponse
    {
        $this->authorize('delete', $programme);

        $this->programmeService->delete($programme);

        return ApiResponse::success(null, 'Programme deleted.');
    }

    public function storeDocument(StoreProgrammeDocumentRequest $request, Programme $programme): JsonResponse
    {
        $this->authorize('update', $programme);

        $document = $this->programmeService->storeDocument(
            $programme,
            $request->file('file'),
            $request->validated('title'),
        );

        return ApiResponse::success(new DocumentResource($document), status: 201);
    }

    public function destroyDocument(Programme $programme, Document $document): JsonResponse
    {
        $this->authorize('update', $programme);

        $this->programmeService->deleteDocument($programme, $document);

        return ApiResponse::success(null, 'Document deleted.');
    }
}
